<?php

use App\Http\Middleware\AuthenticatePortalApiToken;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\EnsurePortalAuthenticated;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogPortalActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'portal.auth' => EnsurePortalAuthenticated::class,
            'admin.auth' => EnsureAdminAuthenticated::class,
            'portal.log' => LogPortalActivity::class,
            'portal.api' => AuthenticatePortalApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The public API always answers JSON errors, even when the caller sends no Accept header.
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            if ($request->routeIs('portal.logout')) {
                $request->session()->forget('portal_id');
                $fallback = route('portal.login');
            } elseif ($request->routeIs('admin.logout')) {
                $request->session()->forget('admin_id');
                $fallback = route('admin.login');
            } elseif ($request->is('admin', 'admin/*')) {
                $fallback = route($request->session()->has('admin_id') ? 'admin.dashboard' : 'admin.login');
            } else {
                $fallback = route($request->session()->has('portal_id') ? 'dashboard' : 'portal.login');
            }

            $request->session()->regenerateToken();
            $referer = $request->headers->get('referer');
            $sameOriginReferer = $referer
                && str_starts_with($referer, $request->getSchemeAndHttpHost() . '/');

            return redirect()->to($sameOriginReferer ? $referer : $fallback)
                ->with('warning', 'This page security token is out of date. Your request was not processed; please review your changes and try again.');
        });
    })
    ->create();
