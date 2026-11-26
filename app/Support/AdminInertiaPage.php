<?php

namespace App\Support;

use Inertia\Inertia;
use Inertia\Response;

class AdminInertiaPage
{
    public static function render(string $component, array $props): Response
    {
        $admin = view()->shared('currentAdmin');

        return Inertia::render($component, [
            'currentYear' => date('Y'),
            ...$props,
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
        ])->rootView('admin-app');
    }
}
