<?php

use App\Http\Controllers\Api\TenantDataController;
use Illuminate\Support\Facades\Route;

// Token in the URL path:  GET /api/get_categories/{token}
Route::get('get_categories/{token}', [TenantDataController::class, 'categories'])
    ->middleware('portal.api')->where('token', '[A-Za-z0-9_\-]+');

// More URL-token endpoints (same token-in-path style as get_categories)
Route::middleware('portal.api')->group(function () {
    Route::get('get_tables/{token}', [TenantDataController::class, 'tableList'])->where('token', '[A-Za-z0-9_\\-]+');
    Route::get('get_table/{table}/{token}', [TenantDataController::class, 'rows'])
        ->where(['table' => '[A-Za-z0-9_]+', 'token' => '[A-Za-z0-9_\\-]+']);
    Route::get('get_all_data/{token}', [TenantDataController::class, 'everything'])->where('token', '[A-Za-z0-9_\\-]+');
});

// Public, read-only, token-protected API. Served under /api (see bootstrap/app.php).
Route::prefix('v1')->middleware('portal.api')->group(function () {
    Route::get('/', [TenantDataController::class, 'index']);
    Route::get('/tables', [TenantDataController::class, 'tableList']);
    Route::get('/tables/{table}', [TenantDataController::class, 'rows'])->where('table', '[A-Za-z0-9_]+');
    Route::get('/tables/{table}/{id}', [TenantDataController::class, 'find'])->where('table', '[A-Za-z0-9_]+');
});

// Anything else under /api answers JSON (the web fallback would redirect to the dashboard).
Route::any('{any?}', fn () => response()->json(['error' => 'not_found', 'message' => 'Unknown API endpoint.'], 404))
    ->where('any', '.*');
