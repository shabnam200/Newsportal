<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->has('admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Login', [
            'csrfToken' => csrf_token(),
            'logoUrl' => url('media/assets/img/icons/logo.png?size=128'),
            'submitUrl' => route('admin.login.submit'),
            'oldEmail' => old('email') ?? '',
        ])->toResponse($request)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $data['email'])->first();

        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_id');
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
