<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\GraceRequest;
use App\Models\Package;
use App\Models\Portal;
use App\Models\PortalSupportSession;
use App\Models\PurchaseRequest;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Portal-side subscription screens: the dashboard (with the "expiring soon" banner),
 * the grace-period request and the renew / purchase request. Requests are only
 * *requests*: an admin approves them from the admin panel.
 */
class PortalBillingController extends Controller
{
    public function dashboard(Request $request, SubscriptionService $subs)
    {
        return Inertia::render('Portal/DashboardHost', ['mode' => 'portal'])
            ->rootView('dashboard')
            ->withViewData($this->dashboardViewData($request, $subs));
    }

    /** Data shared by the portal dashboard and the legacy module pages hosted in its shell. */
    public function dashboardViewData(Request $request, SubscriptionService $subs): array
    {
        /** @var Portal $portal */
        $portal = $request->attributes->get('portal');

        $activeSupportSession = PortalSupportSession::where('portal_id', $portal->id)
            ->whereNull('ended_at')
            ->where('last_activity_at', '>=', now()->subMinutes(30))
            ->latest('started_at')
            ->first();

        return [
            'billing' => $this->panel($portal, $subs),
            'activeSupportSession' => $activeSupportSession,
            'inertiaPage' => true,
        ];
    }

    public function renderAdminSupportDashboard(
        Portal $portal,
        Admin $admin,
        PortalSupportSession $supportSession,
        SubscriptionService $subs
    ) {
        TenantManager::connect($portal);
        $billing = $this->panel($portal, $subs);

        $viewData = [
            'billing' => $billing,
            'currentPortal' => $portal,
            'subscriptionInfo' => $billing['info'],
            'adminSupport' => true,
            'supportAdmin' => $admin,
            'adminSupportSession' => $supportSession,
            'activeSupportSession' => null,
            'inertiaPage' => true,
        ];

        return Inertia::render('Portal/DashboardHost', ['mode' => 'admin-support'])
            ->rootView('dashboard')
            ->withViewData($viewData);
    }

    public function requestGrace(Request $request, SubscriptionService $subs)
    {
        $portal = $request->attributes->get('portal');
        $info = $subs->info($portal);

        if (!$info['subscription'] || !in_array($info['state'], ['active', 'grace'], true)) {
            return $this->fail('until_date', 'No running subscription found.');
        }

        // The grace period can only be asked for in the last N days of the paid period (or once it is overdue).
        if ($info['state'] === 'active' && $info['days_left'] > $info['window_days']) {
            return $this->fail('until_date', "You can request a grace period only during the last {$info['window_days']} day(s) of your subscription.");
        }

        if ($portal->graceRequests()->where('status', 'pending')->exists()) {
            return $this->fail('until_date', 'You already have a grace period request waiting for approval.');
        }

        $min = $info['grace_ends_at']->copy()->addDay();
        $max = $info['subscription']->ends_at->copy()->addDays((int) config('billing.max_grace_days'));

        if ($min->gt($max)) {
            return $this->fail('until_date', 'The maximum grace period is already in place for this subscription.');
        }

        $data = $request->validate([
            'until_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $min->toDateString(), 'before_or_equal:' . $max->toDateString()],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'until_date.after_or_equal' => 'Choose a date after ' . $min->format('d M Y') . ' (your current grace ends ' . $info['grace_ends_at']->format('d M Y') . ').',
            'until_date.before_or_equal' => 'The latest date you can request is ' . $max->format('d M Y') . '.',
        ]);

        GraceRequest::create([
            'portal_id' => $portal->id,
            'until_date' => $data['until_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return $this->requestSuccess(
            $request,
            'Grace period request sent. You will be able to keep using the portal once the administrator approves it.'
        );
    }

    public function requestPurchase(Request $request, SubscriptionService $subs)
    {
        $portal = $request->attributes->get('portal');

        $data = $request->validate([
            'package_id' => ['required', 'integer'],
            'months' => ['required', 'integer', 'min:1', 'max:60'],
            'method' => ['required', 'in:' . implode(',', array_keys(config('billing.methods')))],
            'trx_id' => ['nullable', 'string', 'max:100', 'required_unless:method,cash'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'package_id.required' => 'Please choose a plan.',
            'trx_id.required_unless' => 'Enter the transaction ID of your payment.',
        ]);

        if ($portal->purchaseRequests()->where('status', 'pending')->exists()) {
            return $this->fail('package_id', 'You already have a renewal request waiting for approval.');
        }

        $package = Package::where('active', true)->where('is_trial', false)->find($data['package_id']);

        if (!$package) {
            return $this->fail('package_id', 'This plan is not available.');
        }

        $quote = $subs->quote($portal, $package);
        $min = max(1, $quote['due']);
        $max = $quote['remaining'];
        $months = (int) $data['months'];

        if ($months < $min || $months > $max) {
            return $this->fail('months', "For this plan you can pay between {$min} and {$max} month(s).");
        }

        $extras = $subs->extrasBreakdown($portal);
        $amount = round($quote['price'] * $months + $extras['one_time'] + $extras['cycle'] * $months, 2);

        PurchaseRequest::create([
            'portal_id' => $portal->id,
            'package_id' => $package->id,
            'months' => $months,
            'amount' => $amount,
            'method' => $data['method'],
            'trx_id' => $data['trx_id'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        return $this->requestSuccess(
            $request,
            'Renewal request sent. Your plan is extended as soon as the administrator confirms your payment.'
        );
    }

    private function requestSuccess(Request $request, string $message)
    {
        $supportSession = $request->attributes->get('admin_support_session');
        if ($supportSession instanceof PortalSupportSession) {
            return redirect()->route('admin.support.show', $supportSession)->with('billing_success', $message);
        }

        return redirect()->route('dashboard')->with('billing_success', $message);
    }

    private function fail(string $field, string $message)
    {
        return back()->withInput()->withErrors([$field => $message]);
    }

    /** Everything the dashboard banner + the two popups need. */
    private function panel(Portal $portal, SubscriptionService $subs): array
    {
        $info = $subs->info($portal);
        $sub = $info['subscription'];

        $showBanner = false;
        $message = null;
        if ($sub) {
            if ($info['state'] === 'grace') {
                $showBanner = true;
                $message = "Your grace period ends in {$info['grace_days_left']} day(s). After that, access stops.";
            } elseif ($info['state'] === 'active' && $info['days_left'] <= $info['window_days']) {
                $showBanner = true;
                $message = $info['days_left'] <= 1
                    ? 'Your subscription will expire today.'
                    : "Your subscription will expire in {$info['days_left']} day(s).";
            }
        }

        // Grace option: only inside the package's window (last N days, or already overdue).
        $grace = ['can_request' => false, 'min' => null, 'max' => null];
        if ($sub && in_array($info['state'], ['active', 'grace'], true)) {
            $min = $info['grace_ends_at']->copy()->addDay();
            $max = $sub->ends_at->copy()->addDays((int) config('billing.max_grace_days'));
            $inWindow = $info['state'] === 'grace' || $info['days_left'] <= $info['window_days'];
            $grace = ['can_request' => $inWindow && $min->lte($max), 'min' => $min, 'max' => $max];
        }

        $extras = $subs->extrasBreakdown($portal);

        $packages = Package::where('active', true)->where('is_trial', false)->orderBy('price')->get()
            ->map(function (Package $p) use ($portal, $subs) {
                $q = $subs->quote($portal, $p);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'term_months' => $p->term_months,
                    'price' => $q['price'],
                    'min_months' => max(1, $q['due']),
                    'max_months' => $q['remaining'],
                    'due' => $q['due'],
                ];
            });

        return [
            'info' => $info,
            'banner' => ['show' => $showBanner, 'message' => $message, 'is_grace' => $info['state'] === 'grace'],
            'grace' => $grace,
            'packages' => $packages,
            'extras' => $extras,
            'pendingGrace' => $portal->graceRequests()->where('status', 'pending')->latest()->first(),
            'lastGrace' => $portal->graceRequests()->where('status', '!=', 'pending')->latest('decided_at')->first(),
            'pendingPurchase' => $portal->purchaseRequests()->with('package')->where('status', 'pending')->latest()->first(),
            'lastPurchase' => $portal->purchaseRequests()->with('package')->where('status', '!=', 'pending')->latest('decided_at')->first(),
        ];
    }
}
