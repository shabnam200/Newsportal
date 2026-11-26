<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Answers addresses that have no page of their own, so a stray URL never ends on a 404 / 405 error page:
 * an old bookmark, a Phoenix demo link such as /index.html, GET /logout (logging out is POST only),
 * or a page restored by the browser after the session ended.
 * Visitors go to their home page: the dashboard when logged in, the login page otherwise.
 */
class FallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        // A missing file (js, css, images...) must stay a real 404, never an HTML page in its place.
        if (preg_match('/\.(js|css|map|png|jpe?g|gif|svg|ico|webp|woff2?|ttf|eot|json|mp4|webm)$/i', $request->path())) {
            abort(404);
        }

        if ($request->is('admin', 'admin/*')) {
            return redirect()->route($request->session()->has('admin_id') ? 'admin.dashboard' : 'admin.login');
        }

        return redirect()->route($request->session()->has('portal_id') ? 'dashboard' : 'portal.login');
    }
}
