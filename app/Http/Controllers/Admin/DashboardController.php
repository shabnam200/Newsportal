<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portal;
use App\Models\PortalActivity;
use App\Services\ActivityMonitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, ActivityMonitor $monitor)
    {
        $stats = [
                'total' => Portal::count(),
                'active' => Portal::where('status', 'active')->count(),
                'suspended' => Portal::where('status', 'suspended')->count(),
                'requests24' => PortalActivity::where('event', 'request')->where('created_at', '>=', now()->subDay())->count(),
        ];

        $flagged = collect($monitor->flaggedPortals())->map(function (array $item) {
            $portal = $item['portal'];

            return [
                'name' => $portal->portal_name,
                'url' => route('admin.portals.show', [$portal->id, 'tab' => 'activity']),
                'toggleUrl' => route('admin.portals.toggle', $portal),
                'alerts' => $item['alerts'],
            ];
        })->values();

        $recentPortals = Portal::latest()->limit(5)->get()->map(fn (Portal $portal) => [
            'name' => $portal->portal_name,
            'url' => route('admin.portals.show', $portal),
            'createdAt' => $portal->created_at?->diffForHumans(),
        ]);

        $recent = PortalActivity::with('portal:id,portal_name')
            ->orderByDesc('created_at')->orderByDesc('id')->limit(10)->get()
            ->map(fn (PortalActivity $activity) => [
                'time' => $activity->created_at?->format('d M H:i:s'),
                'portalName' => $activity->portal?->portal_name ?? '#' . $activity->portal_id,
                'portalUrl' => route('admin.portals.show', [$activity->portal_id, 'tab' => 'activity']),
                'method' => $activity->method,
                'path' => $activity->path,
                'event' => $activity->event,
                'status' => $activity->status,
                'ip' => $activity->ip,
                'queries' => $activity->db_queries,
                'writes' => $activity->db_writes,
                'duration' => $activity->duration_ms,
                'userAgent' => $activity->user_agent,
            ]);

        $admin = view()->shared('currentAdmin');

        return Inertia::render('Admin/Dashboard', [
            'title' => 'Dashboard · Admin',
            'pageTitle' => 'Admin Dashboard',
            'pageSubtitle' => 'Here’s what’s going on across your portals right now',
            'currentYear' => date('Y'),
            'csrfToken' => csrf_token(),
            'stats' => $stats,
            'flagged' => $flagged,
            'recentPortals' => $recentPortals,
            'recent' => $recent,
            'pendingRequestCount' => (int) view()->shared('pendingRequestCount', 0),
            'adminLayout' => [
                'admin' => ['name' => $admin?->name, 'email' => $admin?->email],
                'pendingRequestCount' => (int) view()->shared('pendingRequestCount', 0),
                'csrfToken' => csrf_token(),
                'urls' => [
                    'dashboard' => route('admin.dashboard'),
                    'portals' => route('admin.portals.index'),
                    'requests' => route('admin.requests.index'),
                    'activity' => route('admin.activity.index'),
                    'packages' => route('admin.packages.index'),
                    'logout' => route('admin.logout'),
                    'logo' => url('media/assets/img/icons/logo.png?size=128'),
                ],
            ],
        ])->rootView('admin-app')->toResponse($request);
    }
}
