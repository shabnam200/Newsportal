<?php

namespace App\Services;

use App\Models\Portal;
use App\Models\PortalActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ActivityMonitor
{
    public const RANGES = [
        '1h' => 'Last hour',
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
    ];

    /** Store one activity row. Never throws: logging must not break the portal. */
    public function log(Request $request, Portal $portal, string $event, int $status, int $durationMs = 0, int $queries = 0, int $writes = 0): void
    {
        try {
            PortalActivity::create([
                'portal_id' => $portal->id,
                'event' => $event,
                'method' => $request->method(),
                'path' => Str::limit('/' . ltrim($request->path(), '/'), 500, ''),
                'route_name' => $request->route()?->getName(),
                'status' => $status,
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                'duration_ms' => $durationMs,
                'db_queries' => $queries,
                'db_writes' => $writes,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function since(?string $range): Carbon
    {
        return match ($range) {
            '1h' => now()->subHour(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }

    /** Applies the filter form (range, event, status, method, search) to a log query. */
    public function filtered(Builder $query, Request $request): Builder
    {
        $query->where('created_at', '>=', $this->since($request->query('range')));

        if (in_array($event = $request->query('event'), ['request', 'login', 'login_blocked'], true)) {
            $query->where('event', $event);
        }

        switch ($request->query('status')) {
            case '2xx': $query->whereBetween('status', [200, 299]); break;
            case '3xx': $query->whereBetween('status', [300, 399]); break;
            case '4xx': $query->whereBetween('status', [400, 499]); break;
            case '5xx': $query->whereBetween('status', [500, 599]); break;
            case 'error': $query->where('status', '>=', 400); break;
        }

        $method = strtoupper((string) $request->query('method'));
        if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $query->where('method', $method);
        }

        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $query->where(fn ($w) => $w->where('path', 'like', "%{$term}%")->orWhere('ip', 'like', "%{$term}%"));
        }

        return $query;
    }

    public function summary(Portal $portal, Carbon $since): array
    {
        $all = fn () => PortalActivity::where('portal_id', $portal->id)->where('created_at', '>=', $since);
        $requests = fn () => $all()->where('event', 'request');

        return [
            'requests' => $requests()->count(),
            'errors' => $requests()->where('status', '>=', 400)->count(),
            'ips' => $all()->whereNotNull('ip')->distinct()->count('ip'),
            'writes' => (int) $requests()->sum('db_writes'),
            'avg_ms' => (int) round((float) $requests()->avg('duration_ms')),
            'top_paths' => $requests()
                ->selectRaw('path, count(*) as hits, sum(case when status >= 400 then 1 else 0 end) as errors')
                ->groupBy('path')->orderByDesc('hits')->limit(5)->get(),
            'top_ips' => $all()->whereNotNull('ip')
                ->selectRaw('ip, count(*) as hits, max(created_at) as last_seen')
                ->groupBy('ip')->orderByDesc('hits')->limit(5)->get(),
        ];
    }

    /** Suspicious patterns right now. Each alert: ['level' => danger|warning, 'message' => string]. */
    public function alerts(Portal $portal): array
    {
        $c = config('tenancy.monitor');
        $base = fn () => PortalActivity::where('portal_id', $portal->id);
        $alerts = [];

        $last15 = fn () => $base()->where('event', 'request')->where('created_at', '>=', now()->subMinutes(15));

        $requests = $last15()->count();
        if ($requests >= $c['requests_per_15min']) {
            $alerts[] = ['level' => 'danger', 'message' => "Very high traffic: {$requests} requests in the last 15 minutes."];
        }

        $writes = (int) $last15()->sum('db_writes');
        if ($writes >= $c['writes_per_15min']) {
            $alerts[] = ['level' => 'danger', 'message' => "Unusually many database writes: {$writes} in the last 15 minutes."];
        }

        $hour = now()->subHour();

        $errors = $base()->where('event', 'request')->where('status', '>=', 400)->where('created_at', '>=', $hour)->count();
        if ($errors >= $c['errors_per_hour']) {
            $alerts[] = ['level' => 'warning', 'message' => "{$errors} failed requests (4xx/5xx) in the last hour."];
        }

        $ips = $base()->whereNotNull('ip')->where('created_at', '>=', $hour)->distinct()->count('ip');
        if ($ips >= $c['ips_per_hour']) {
            $alerts[] = ['level' => 'warning', 'message' => "Used from {$ips} different IP addresses in the last hour."];
        }

        $blocked = $base()->where('event', 'login_blocked')->where('created_at', '>=', $hour)->count();
        if ($blocked >= $c['blocked_logins_per_hour']) {
            $alerts[] = ['level' => 'warning', 'message' => "{$blocked} blocked login attempts in the last hour."];
        }

        return $alerts;
    }

    /** Active portals that were busy in the last hour and currently have alerts. */
    public function flaggedPortals(): array
    {
        $ids = PortalActivity::where('created_at', '>=', now()->subHour())->distinct()->pluck('portal_id');
        $flagged = [];

        foreach (Portal::whereIn('id', $ids)->where('status', 'active')->limit(100)->get() as $portal) {
            $alerts = $this->alerts($portal);
            if ($alerts) {
                $flagged[] = ['portal' => $portal, 'alerts' => $alerts];
            }
        }

        return $flagged;
    }
}
