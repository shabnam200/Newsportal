<?php

namespace App\Http\Middleware;

use App\Services\ActivityMonitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Records every request a portal makes (path, status, IP, duration) and how many queries /
 * write-queries it ran on the portal's tenant database. Request bodies and query strings
 * are never stored. Must run AFTER portal.auth (which sets up the tenant connection).
 */
class LogPortalActivity
{
    public function handle(Request $request, Closure $next)
    {
        $portal = $request->attributes->get('portal');
        if (!$portal) {
            return $next($request);
        }

        $start = microtime(true);
        $stats = ['queries' => 0, 'writes' => 0];

        try {
            DB::connection('tenant')->listen(function ($query) use (&$stats) {
                $stats['queries']++;
                if (preg_match('/^\s*(insert|update|delete|replace|drop|alter|truncate|create)\b/i', $query->sql)) {
                    $stats['writes']++;
                }
            });
        } catch (\Throwable $e) {
            // monitoring is best-effort
        }

        $monitor = app(ActivityMonitor::class);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $monitor->log($request, $portal, 'request', 500, (int) round((microtime(true) - $start) * 1000), $stats['queries'], $stats['writes']);
            throw $e;
        }

        $monitor->log($request, $portal, 'request', $response->getStatusCode(), (int) round((microtime(true) - $start) * 1000), $stats['queries'], $stats['writes']);

        return $response;
    }
}
