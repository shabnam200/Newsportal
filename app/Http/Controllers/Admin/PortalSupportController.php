<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PortalBillingController;
use App\Models\Admin;
use App\Models\Portal;
use App\Models\PortalSupportSession;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalSupportController extends Controller
{
    private const IDLE_TIMEOUT_MINUTES = 30;

    public function start(Request $request, Portal $portal)
    {
        if (!$portal->isProvisioned()) {
            return back()->withErrors(['support' => 'Provision this portal before opening a support session.']);
        }

        $admin = Admin::findOrFail($request->session()->get('admin_id'));
        $supportSession = DB::transaction(function () use ($portal, $admin, $request) {
            $supportSession = $portal->supportSessions()->create([
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'portal_name' => $portal->portal_name,
                'started_at' => now(),
                'last_activity_at' => now(),
            ]);

            $supportSession->events()->create([
                'action' => 'started',
                'path' => $request->path(),
            ]);
            $supportSession->events()->create([
                'action' => 'write_enabled',
                'path' => $request->path(),
            ]);

            return $supportSession;
        });

        return redirect()->route('admin.support.show', $supportSession);
    }

    public function show(
        Request $request,
        PortalSupportSession $supportSession,
        PortalBillingController $billing,
        SubscriptionService $subs
    ) {
        $this->authorizeSession($request, $supportSession);

        $portal = $supportSession->portal;
        if (!$portal) {
            abort(404);
        }

        if ($supportSession->ended_at) {
            return redirect()->route('admin.portals.show', $portal)
                ->withErrors(['support' => 'This support session has already ended.']);
        }

        if ($supportSession->last_activity_at
            && $supportSession->last_activity_at->lt(now()->subMinutes(self::IDLE_TIMEOUT_MINUTES))) {
            DB::transaction(function () use ($supportSession, $request) {
                $supportSession->update(['ended_at' => now()]);
                $supportSession->events()->create([
                    'action' => 'timed_out',
                    'path' => $request->path(),
                ]);
            });

            return redirect()->route('admin.portals.show', $portal)
                ->with('warning', 'The support session ended after 30 minutes of inactivity.');
        }

        if (!$portal->isProvisioned()) {
            DB::transaction(function () use ($supportSession, $request) {
                $supportSession->update(['ended_at' => now()]);
                $supportSession->events()->create([
                    'action' => 'unavailable',
                    'path' => $request->path(),
                ]);
            });

            return redirect()->route('admin.portals.show', $portal)
                ->withErrors(['support' => 'This portal database is not ready for support access.']);
        }

        DB::transaction(function () use ($supportSession, $request) {
            $supportSession->update(['last_activity_at' => now()]);
            $supportSession->events()->create([
                'action' => 'viewed',
                'path' => $request->path(),
            ]);
        });

        $admin = Admin::findOrFail($request->session()->get('admin_id'));

        return $billing->renderAdminSupportDashboard($portal, $admin, $supportSession, $subs);
    }

    public function requestGrace(
        Request $request,
        PortalSupportSession $supportSession,
        PortalBillingController $billing,
        SubscriptionService $subs
    ) {
        if ($response = $this->prepareSupportWrite($request, $supportSession, 'grace_attempt')) {
            return $response;
        }

        return $billing->requestGrace($request, $subs);
    }

    public function requestPurchase(
        Request $request,
        PortalSupportSession $supportSession,
        PortalBillingController $billing,
        SubscriptionService $subs
    ) {
        if ($response = $this->prepareSupportWrite($request, $supportSession, 'purchase_attempt')) {
            return $response;
        }

        return $billing->requestPurchase($request, $subs);
    }

    public function end(Request $request, PortalSupportSession $supportSession)
    {
        $this->authorizeSession($request, $supportSession);

        if (!$supportSession->ended_at) {
            DB::transaction(function () use ($supportSession, $request) {
                $supportSession->update([
                    'ended_at' => now(),
                    'last_activity_at' => now(),
                ]);
                $supportSession->events()->create([
                    'action' => 'ended',
                    'path' => $request->path(),
                ]);
            });
        }

        if ($supportSession->portal_id) {
            return redirect()->route('admin.portals.show', $supportSession->portal_id)
                ->with('success', 'Admin support session ended.');
        }

        return redirect()->route('admin.portals.index')
            ->with('success', 'Admin support session ended.');
    }

    private function prepareSupportWrite(
        Request $request,
        PortalSupportSession $supportSession,
        string $action
    ): ?RedirectResponse {
        $this->authorizeSession($request, $supportSession);

        $portal = $supportSession->portal;
        if (!$portal) {
            abort(404);
        }

        if ($supportSession->ended_at) {
            return redirect()->route('admin.portals.show', $portal)
                ->withErrors(['support' => 'This support session has already ended.']);
        }

        if ($supportSession->last_activity_at
            && $supportSession->last_activity_at->lt(now()->subMinutes(self::IDLE_TIMEOUT_MINUTES))) {
            DB::transaction(function () use ($supportSession, $request) {
                $supportSession->update(['ended_at' => now()]);
                $supportSession->events()->create([
                    'action' => 'timed_out',
                    'path' => $request->path(),
                ]);
            });

            return redirect()->route('admin.portals.show', $portal)
                ->with('warning', 'The support session ended after 30 minutes of inactivity.');
        }

        if (!$portal->isProvisioned()) {
            DB::transaction(function () use ($supportSession, $request) {
                $supportSession->update(['ended_at' => now()]);
                $supportSession->events()->create([
                    'action' => 'unavailable',
                    'path' => $request->path(),
                ]);
            });

            return redirect()->route('admin.portals.show', $portal)
                ->withErrors(['support' => 'This portal database is not ready for support access.']);
        }

        TenantManager::connect($portal);
        DB::transaction(function () use ($supportSession, $request, $action) {
            $supportSession->update(['last_activity_at' => now()]);
            $supportSession->events()->create([
                'action' => $action,
                'path' => $request->path(),
            ]);
        });
        $request->attributes->set('portal', $portal);
        $request->attributes->set('admin_support_session', $supportSession);

        return null;
    }

    private function authorizeSession(Request $request, PortalSupportSession $supportSession): void
    {
        abort_unless((int) $supportSession->admin_id === (int) $request->session()->get('admin_id'), 403);
    }
}
