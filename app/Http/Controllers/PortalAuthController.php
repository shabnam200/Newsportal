<?php

namespace App\Http\Controllers;

use App\Models\Portal;
use App\Services\ActivityMonitor;
use App\Services\SubscriptionService;
use App\Services\TenantProvisioner;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class PortalAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->has('portal_id')) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Portal/Login', [
            'status' => $request->session()->get('status'),
            'oldMobile' => old('editor_mobile') ?? '',
            'csrfToken' => csrf_token(),
            'logoUrl' => url('media/assets/img/icons/logo.png?size=128'),
            'signupUrl' => route('portal.signup'),
            'submitUrl' => route('portal.login.submit'),
        ])->toResponse($request)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function showSignup(Request $request)
    {
        if ($request->session()->has('portal_id')) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Portal/Signup', [
            'csrfToken' => csrf_token(),
            'logoUrl' => url('media/assets/img/icons/logo.png?size=128'),
            'submitUrl' => route('portal.signup.submit'),
            'loginUrl' => route('portal.login'),
            'oldInput' => [
                'portal_name' => old('portal_name') ?? '',
                'portal_reg_no' => old('portal_reg_no') ?? '',
                'editor_name' => old('editor_name') ?? '',
                'editor_email' => old('editor_email') ?? '',
                'editor_mobile' => old('editor_mobile') ?? '',
            ],
        ])->toResponse($request)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Login with mobile number + password only.
     */
    public function login(Request $request, TenantProvisioner $provisioner, SubscriptionService $subs, ActivityMonitor $monitor)
    {
        $data = $request->validate([
            'editor_mobile' => ['required', 'regex:' . Portal::mobileRegex()],
            'password' => ['required', 'string', 'max:100'],
        ], [
            'editor_mobile.regex' => 'Enter a valid Bangladeshi mobile number (e.g. 017XXXXXXXX).',
        ]);

        $mobile = Portal::normalizeMobile($data['editor_mobile']);
        $keep = $request->only('editor_mobile');

        // The mobile number is the login id. Older data may hold the same number under more than one
        // portal name, so the password decides which portal this is.
        $portal = Portal::where('editor_mobile', $mobile)
            ->whereNotNull('password')
            ->get()
            ->first(fn (Portal $p) => Hash::check($data['password'], $p->password));

        if (!$portal) {
            $known = Portal::where('editor_mobile', $mobile)->first();

            if ($known && $known->password === null) {
                // Portal created before passwords existed: it has to set one through Sign up.
                return back()->withInput($keep)->withErrors([
                    'editor_mobile' => 'This account has no password yet. Please use Sign up to set one.',
                ]);
            }

            if ($known) {
                $monitor->log($request, $known, 'login_failed', 401);
            }

            return back()->withInput($keep)->withErrors([
                'editor_mobile' => 'Mobile number or password is incorrect.',
            ]);
        }

        if (!$portal->isActive()) {
            $monitor->log($request, $portal, 'login_blocked', 403);
            return back()->withInput($keep)->withErrors([
                'editor_mobile' => 'This portal has been suspended. Please contact the administrator.',
            ]);
        }

        $subs->ensureTrial($portal);

        if (!$subs->isUsable($portal)) {
            $monitor->log($request, $portal, 'login_blocked', 403);
            return back()->withInput($keep)->withErrors([
                'editor_mobile' => 'Your subscription has expired. Please contact the administrator to renew.',
            ]);
        }

        // Normally done at sign up; this covers a sign up whose database setup failed earlier.
        if (!$portal->isProvisioned()) {
            try {
                $provisioner->provision($portal);
            } catch (Throwable $e) {
                return back()->withInput($keep)->withErrors([
                    'editor_mobile' => 'Could not set up your portal database right now. Please try again or contact support.',
                ]);
            }
        }

        return $this->enter($request, $portal, $monitor);
    }

    /**
     * Sign up: creates the portal (with its password) and signs the editor in.
     */
    public function signup(Request $request, TenantProvisioner $provisioner, SubscriptionService $subs, ActivityMonitor $monitor)
    {
        $data = $request->validate([
            'portal_name' => ['required', 'string', 'max:100'],
            'portal_reg_no' => ['nullable', 'string', 'max:100'],
            'editor_name' => ['nullable', 'string', 'max:100'],
            'editor_email' => ['nullable', 'email', 'max:150'],
            'editor_mobile' => ['required', 'regex:' . Portal::mobileRegex()],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ], [
            'editor_mobile.regex' => 'Enter a valid Bangladeshi mobile number (e.g. 017XXXXXXXX).',
            'password.confirmed' => 'Password and confirm password do not match.',
        ]);

        $mobile = Portal::normalizeMobile($data['editor_mobile']);
        $name = trim($data['portal_name']);
        $keep = $request->except('password', 'password_confirmation');

        // A portal created before passwords existed has no password. Signing up with its exact name and
        // number sets one (this is what its first login used to do), so those portals are not locked out.
        $portal = Portal::where('editor_mobile', $mobile)
            ->where('portal_name', $name)
            ->whereNull('password')
            ->first();

        // The mobile number is the login id, so it can only belong to one portal.
        if (!$portal && Portal::where('editor_mobile', $mobile)->exists()) {
            return back()->withInput($keep)->withErrors([
                'editor_mobile' => 'This mobile number is already registered. Please log in instead.',
            ]);
        }

        if ($portal && !$portal->isActive()) {
            $monitor->log($request, $portal, 'login_blocked', 403);
            return back()->withInput($keep)->withErrors([
                'editor_mobile' => 'This portal has been suspended. Please contact the administrator.',
            ]);
        }

        if (!$portal) {
            $portal = Portal::create([
                'portal_name' => $name,
                'editor_mobile' => $mobile,
                'password' => $data['password'],
                'status' => 'active',
            ]);
        } else {
            $portal->password = $data['password'];
            $portal->save();
        }

        // New portals get the one-time 7 day trial automatically
        $subs->ensureTrial($portal);

        if (!$subs->isUsable($portal)) {
            $monitor->log($request, $portal, 'login_blocked', 403);
            return redirect()->route('portal.login')->withErrors([
                'editor_mobile' => 'Your subscription has expired. Please contact the administrator to renew.',
            ]);
        }

        // Fill the optional info if the user gave it
        foreach (['portal_reg_no', 'editor_name', 'editor_email'] as $field) {
            if (!empty($data[$field])) {
                $portal->{$field} = $data[$field];
            }
        }

        // Create the tenant database
        if (!$portal->isProvisioned()) {
            try {
                $provisioner->provision($portal);
            } catch (Throwable $e) {
                $portal->save();
                return back()->withInput($keep)->withErrors([
                    'portal_name' => 'Your account was created, but the portal database could not be set up right now. Please log in again in a moment or contact support.',
                ]);
            }
        }

        return $this->enter($request, $portal, $monitor);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('portal_id');
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('status', 'You have been logged out.');
    }

    /** Start the portal session and open the dashboard. */
    private function enter(Request $request, Portal $portal, ActivityMonitor $monitor): RedirectResponse
    {
        $portal->last_login_at = now();
        $portal->save();

        $request->session()->regenerate();
        $request->session()->put('portal_id', $portal->id);
        $request->session()->put('portal_login_key', (string) Str::uuid());
        $monitor->log($request, $portal, 'login', 302);

        return redirect()->route('dashboard');
    }
}
