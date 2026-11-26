<?php

namespace App\Http\Middleware;

use App\Models\Portal;
use App\Services\ActivityMonitor;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public API guard. The caller sends the portal's secret token:
 *     Authorization: Bearer <token>      (or the header  X-API-Token: <token>)
 * The token is looked up by its SHA-256 hash; it identifies exactly ONE portal, and from here on
 * DB::connection('tenant') is that portal's own database - nothing else can be reached.
 * The URL form keeps the token in server/proxy access logs and browser history; header form does not.
 */
class AuthenticatePortalApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $failKey = 'portal-api-fail:' . $request->ip();
        $maxFails = (int) config('portal_api.failed_attempts_per_minute', 20);

        if (RateLimiter::tooManyAttempts($failKey, $maxFails)) {
            return $this->error('too_many_attempts', 'Too many invalid tokens from this IP address. Try again in a minute.', 429,
                ['Retry-After' => RateLimiter::availableIn($failKey)]);
        }

        // Token in the URL path (/api/get_categories/{token}) or in a header (Authorization: Bearer / X-API-Token).
        $token = $request->route('token') ?: $request->bearerToken() ?: $request->header('X-API-Token');
        $token = is_string($token) ? trim($token) : '';
        $request->route()?->forgetParameter('token');

        if ($token === '' || strlen($token) > 200) {
            RateLimiter::hit($failKey, 60);

            return $this->error('missing_token', 'Send your portal token as "Authorization: Bearer <token>".', 401);
        }

        $portal = Portal::where('api_token_hash', hash('sha256', $token))->first();

        if (!$portal) {
            RateLimiter::hit($failKey, 60);

            return $this->error('invalid_token', 'This token is not valid (it may have been regenerated or revoked).', 401);
        }

        if (!$portal->isActive() || !$portal->isProvisioned()) {
            return $this->error('portal_unavailable', 'This portal is suspended or its database is not ready.', 403);
        }

        $info = app(SubscriptionService::class)->info($portal);

        if (!in_array($info['state'], ['active', 'grace'], true)) {
            return $this->error('subscription_expired', 'This portal\'s subscription has expired.', 403);
        }

        // Per-token limit (a leaked token cannot hammer the server).
        $limitKey = 'portal-api:' . $portal->id;
        $limit = (int) config('portal_api.rate_limit_per_minute', 120);

        if (RateLimiter::tooManyAttempts($limitKey, $limit)) {
            return $this->error('rate_limited', 'Rate limit reached for this token.', 429,
                ['Retry-After' => RateLimiter::availableIn($limitKey)]);
        }

        RateLimiter::hit($limitKey, 60);

        TenantManager::connect($portal);
        $request->attributes->set('portal', $portal);

        // Remember the last use (at most once a minute, so reads do not turn into writes).
        if (!$portal->api_token_last_used_at || $portal->api_token_last_used_at->lt(now()->subMinute())) {
            Portal::whereKey($portal->id)->toBase()->update(['api_token_last_used_at' => now()]);
        }

        $start = microtime(true);
        $response = $next($request);

        app(ActivityMonitor::class)->log($request, $portal, 'request', $response->getStatusCode(),
            (int) round((microtime(true) - $start) * 1000));

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($limitKey, $limit));
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function error(string $code, string $message, int $status, array $headers = []): JsonResponse
    {
        return response()->json(['error' => $code, 'message' => $message], $status, $headers);
    }
}
