<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portal;
use App\Models\PortalActivity;
use App\Services\ActivityMonitor;
use App\Support\AdminInertiaPage;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request, ActivityMonitor $monitor)
    {
        $query = PortalActivity::with('portal:id,portal_name');

        if ($portalId = $request->query('portal_id')) {
            $query->where('portal_id', $portalId);
        }

        $logs = $monitor->filtered($query, $request)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return AdminInertiaPage::render('Admin/Activity/Index', [
            'title' => 'Activity monitor · Admin', 'pageTitle' => 'Activity monitor', 'pageSubtitle' => null,
            'logs' => $logs,
            'ranges' => ActivityMonitor::RANGES,
            'portals' => Portal::orderBy('portal_name')->limit(300)->get(['id', 'portal_name']),
            'flagged' => collect($monitor->flaggedPortals())->map(fn ($item) => [
                'portal_name' => $item['portal']->portal_name,
                'url' => route('admin.portals.show', [$item['portal'], 'tab' => 'activity']),
                'alerts' => $item['alerts'],
            ])->values(),
            'filters' => $request->only(['portal_id', 'range', 'event', 'status', 'q']),
            'portalUrls' => Portal::orderBy('portal_name')->limit(300)->get(['id'])->mapWithKeys(fn ($portal) => [
                $portal->id => route('admin.portals.show', [$portal->id, 'tab' => 'activity']),
            ]),
        ]);
    }
}
