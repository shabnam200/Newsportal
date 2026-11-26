<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\GraceRequest;
use App\Models\PurchaseRequest;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('admin_id');
        $admin = $id ? Admin::find($id) : null;

        if (!$admin) {
            $request->session()->forget('admin_id');
            return redirect()->route('admin.login');
        }

        view()->share('currentAdmin', $admin);
        view()->share('pendingRequestCount',
            GraceRequest::where('status', 'pending')->count() + PurchaseRequest::where('status', 'pending')->count());

        $response = $next($request);

        // Never let the browser cache admin pages: after logout the Back button must not show them again.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
