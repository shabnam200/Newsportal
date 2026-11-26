<?php

namespace App\Http\Middleware;

use App\Models\Portal;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;

class EnsurePortalAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('portal_id');

        // A visitor who has not logged in yet is not an expired session.
        if (!$id) {
            return redirect()->route('portal.login');
        }

        $portal = $id ? Portal::find($id) : null;

        if (!$portal || !$portal->isActive() || !$portal->isProvisioned()) {
            $request->session()->forget('portal_id');
            return redirect()->route('portal.login')
                ->withErrors(['editor_mobile' => 'Session expired or portal is not active.']);
        }

        $info = app(SubscriptionService::class)->info($portal);

        if (!in_array($info['state'], ['active', 'grace'], true)) {
            $request->session()->forget('portal_id');
            return redirect()->route('portal.login')
                ->withErrors(['editor_mobile' => 'Your subscription has expired. Please contact the administrator to renew.']);
        }

        view()->share('subscriptionInfo', $info);

        // From here on, DB::connection('tenant') is this portal's own database.
        TenantManager::connect($portal);

        $request->attributes->set('portal', $portal);
        view()->share('currentPortal', $portal);

        $response = $next($request);

        // Never let the browser cache portal pages: after logout the Back button must not show the old dashboard.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
