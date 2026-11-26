<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Portal;
use App\Models\PortalActivity;
use App\Models\PortalSupportEvent;
use App\Services\ActivityMonitor;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use App\Services\TenantProvisioner;
use App\Support\AdminInertiaPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PortalController extends Controller
{
    public function index(Request $request, SubscriptionService $subs)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $portals = Portal::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('portal_name', 'like', "%{$q}%")
                  ->orWhere('editor_name', 'like', "%{$q}%")
                  ->orWhere('editor_email', 'like', "%{$q}%")
                  ->orWhere('editor_mobile', 'like', "%{$q}%")
                  ->orWhere('portal_reg_no', 'like', "%{$q}%");
            }))
            ->when(in_array($status, ['active', 'suspended'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Portal::count(),
            'active' => Portal::where('status', 'active')->count(),
            'suspended' => Portal::where('status', 'suspended')->count(),
            'unprovisioned' => Portal::whereNull('provisioned_at')->count(),
        ];

        $portals->through(function (Portal $portal) use ($subs) {
            $info = $subs->info($portal);
            $subscription = $info['subscription'];

            return [
                'id' => $portal->id,
                'portal_name' => $portal->portal_name,
                'portal_reg_no' => $portal->portal_reg_no,
                'editor_name' => $portal->editor_name,
                'editor_email' => $portal->editor_email,
                'editor_mobile' => $portal->editor_mobile,
                'database_name' => $portal->database_name,
                'provisioned' => $portal->isProvisioned(),
                'status' => $portal->status,
                'last_login_at' => $portal->last_login_at?->diffForHumans() ?? 'Never',
                'show_url' => route('admin.portals.show', $portal),
                'subscription' => $subscription ? [
                    'package_name' => $subscription->package?->name,
                    'state' => $info['state'],
                    'ends_at' => $subscription->ends_at?->format('d M Y'),
                ] : null,
            ];
        });

        return AdminInertiaPage::render('Admin/Portals/Index', [
            'title' => 'Portals | Admin', 'pageTitle' => 'Portals', 'pageSubtitle' => null,
            'portals' => $portals, 'stats' => $stats, 'q' => $q, 'status' => $status,
        ]);
    }

    public function show(Request $request, Portal $portal, SubscriptionService $subs, ActivityMonitor $monitor)
    {
        $tab = in_array($request->query('tab'), ['overview', 'billing', 'activity'], true) ? $request->query('tab') : 'overview';

        $data = [
            'portal' => $portal,
            'tab' => $tab,
            'info' => $subs->info($portal),
            'alerts' => $monitor->alerts($portal),
            'supportEvents' => PortalSupportEvent::whereHas('supportSession', fn ($query) => $query->where('portal_id', $portal->id))
                ->with('supportSession')
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ];

        if ($tab === 'overview') {
            $data['tableCount'] = null;
            $data['dbError'] = null;

            if ($portal->isProvisioned()) {
                try {
                    TenantManager::connect($portal);
                    $data['tableCount'] = count(DB::connection('tenant')->select('SHOW TABLES'));
                } catch (Throwable $e) {
                    $data['dbError'] = $e->getMessage();
                }
            }
        }

        if ($tab === 'billing') {
            $packages = Package::where('active', true)->orderBy('price')->get();
            $data['packages'] = $packages;
            $data['payments'] = $portal->payments()->with('package')->latest('paid_at')->get();
            $data['charges'] = $portal->charges()->latest()->get();
            $data['extras'] = $subs->extrasBreakdown($portal);
            $data['quotes'] = $packages->mapWithKeys(fn ($p) => [$p->id => $subs->quote($portal, $p)]);
        }

        if ($tab === 'activity') {
            $data['summary'] = $monitor->summary($portal, $monitor->since($request->query('range')));
            $data['logs'] = $monitor->filtered(PortalActivity::where('portal_id', $portal->id), $request)
                ->orderByDesc('created_at')->orderByDesc('id')
                ->paginate(25)->withQueryString();
            $data['ranges'] = ActivityMonitor::RANGES;
            $data['filters'] = $request->only(['range', 'event', 'status', 'q']);
        }

        $data['pendingForPortal'] = $portal->graceRequests()->where('status', 'pending')->count()
            + $portal->purchaseRequests()->where('status', 'pending')->count();
        $data['portal'] = [
            'id' => $portal->id,
            'portal_name' => $portal->portal_name,
            'portal_reg_no' => $portal->portal_reg_no,
            'editor_name' => $portal->editor_name,
            'editor_email' => $portal->editor_email,
            'editor_mobile' => $portal->editor_mobile,
            'database_name' => $portal->database_name,
            'status' => $portal->status,
            'provisioned' => $portal->isProvisioned(),
            'provisioned_at' => $portal->provisioned_at?->format('d M Y H:i'),
            'created_at' => $portal->created_at?->format('d M Y H:i'),
            'last_login_at' => $portal->last_login_at?->format('d M Y H:i') ?? 'never',
            'provision_error' => $portal->provision_error,
            'has_api_token' => $portal->hasApiToken(),
            'api_token_last4' => $portal->api_token_last4,
            'api_token_created_at' => $portal->api_token_created_at?->format('d M Y H:i'),
            'api_token_last_used_at' => $portal->api_token_last_used_at?->format('d M Y H:i') ?? 'never',
        ];
        $data['supportEvents'] = $data['supportEvents']->map(fn ($event) => [
            'admin_name' => $event->supportSession?->admin_name ?? 'Admin',
            'action' => ucfirst(str_replace('_', ' ', $event->action)),
            'path' => $event->path,
            'created_at' => $event->created_at?->format('d M Y H:i:s'),
        ]);
        $data['portalUrls'] = [
            'index' => route('admin.portals.index'),
            'show' => route('admin.portals.show', $portal),
            'update' => route('admin.portals.update', $portal),
            'toggle' => route('admin.portals.toggle', $portal),
            'supportStart' => route('admin.portals.support.start', $portal),
            'provision' => route('admin.portals.provision', $portal),
            'apiTokenIssue' => route('admin.portals.api-token.issue', $portal),
            'apiTokenRevoke' => route('admin.portals.api-token.revoke', $portal),
            'destroy' => route('admin.portals.destroy', $portal),
            'payment' => route('admin.portals.payments.store', $portal),
            'charges' => route('admin.portals.charges.store', $portal),
        ];
        $data['newApiToken'] = session('new_api_token');
        $data['requestsUrl'] = route('admin.requests.index');

        return AdminInertiaPage::render('Admin/Portals/Show', [
            'title' => $portal->portal_name . ' · Admin', 'pageTitle' => 'Portal details', 'pageSubtitle' => null,
            ...$data,
        ]);
    }

    public function update(Request $request, Portal $portal)
    {
        $data = $request->validate([
            'portal_name' => ['required', 'string', 'max:100'],
            'portal_reg_no' => ['nullable', 'string', 'max:100'],
            'editor_name' => ['nullable', 'string', 'max:100'],
            'editor_email' => ['nullable', 'email', 'max:150'],
            'editor_mobile' => ['required', 'regex:' . Portal::mobileRegex()],
        ]);
        $data['editor_mobile'] = Portal::normalizeMobile($data['editor_mobile']);

        $clash = Portal::where('portal_name', $data['portal_name'])
            ->where('editor_mobile', $data['editor_mobile'])
            ->where('id', '!=', $portal->id)->exists();

        if ($clash) {
            return back()->withInput()->withErrors(['portal_name' => 'Another portal already uses this name + mobile.']);
        }

        $portal->update($data);

        return back()->with('success', 'Portal updated.');
    }

    public function toggleStatus(Portal $portal)
    {
        $portal->update(['status' => $portal->isActive() ? 'suspended' : 'active']);

        return back()->with('success', 'Portal is now ' . $portal->status . '.');
    }

    public function reprovision(Portal $portal, TenantProvisioner $provisioner)
    {
        try {
            $provisioner->provision($portal);
        } catch (Throwable $e) {
            return back()->withErrors(['provision' => 'Provisioning failed: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Tenant database is ready.');
    }

    /** Create (or replace) the public API token. The plain token is flashed once and never stored. */
    public function issueApiToken(Portal $portal)
    {
        if (!$portal->isProvisioned()) {
            return back()->withErrors(['provision' => 'Create the tenant database first, then create the API token.']);
        }

        $token = $portal->issueApiToken();

        return back()->with('success', 'New API token created. Copy it now - it cannot be shown again.')
            ->with('new_api_token', $token);
    }

    public function revokeApiToken(Portal $portal)
    {
        $portal->revokeApiToken();

        return back()->with('success', 'API token revoked. The API is now closed for this portal.');
    }

    public function destroy(Portal $portal, TenantProvisioner $provisioner)
    {
        try {
            $provisioner->destroy($portal);
        } catch (Throwable $e) {
            return back()->withErrors(['provision' => 'Could not drop the tenant database: ' . $e->getMessage()]);
        }

        $portal->delete();

        return redirect()->route('admin.portals.index')->with('success', 'Portal and its database were deleted.');
    }
}
