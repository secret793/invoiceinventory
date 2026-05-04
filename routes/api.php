<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PowerBIDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| GNSW E-Tracking – Power BI Dashboard Endpoints
|--------------------------------------------------------------------------
| Base URL: /api/powerbi/...
|
| All endpoints accept optional date filters as query parameters:
|   ?date=YYYY-MM-DD              → single day
|   ?from=YYYY-MM-DD&to=YYYY-MM-DD → date range
|
| No authentication required so the endpoints can be consumed directly
| from Power BI or tested freely in Postman.
|--------------------------------------------------------------------------
*/

Route::prefix('powerbi')->group(function () {

    // ── KPI Cards ────────────────────────────────────────────────────────
    // Total Trips, Total Revenue, Active Trips, Avg Duration
    Route::get('/kpi/summary', [PowerBIDashboardController::class, 'kpiSummary']);

    // Overdue Trips, Overdue Revenue, Overdue Rate
    Route::get('/kpi/overdue', [PowerBIDashboardController::class, 'kpiOverdue']);

    // ── Bar Charts ───────────────────────────────────────────────────────
    // Revenue by Station (7 border stations)
    Route::get('/revenue-by-station', [PowerBIDashboardController::class, 'revenueByStation']);

    // Trips by Station
    Route::get('/trips-by-station', [PowerBIDashboardController::class, 'tripsByStation']);

    // Overdue cases by Station
    Route::get('/overdue-by-station', [PowerBIDashboardController::class, 'overdueByStation']);

    // ── Line Charts ──────────────────────────────────────────────────────
    // Trips trend over time  (?granularity=day|week|month)
    Route::get('/trips-over-time', [PowerBIDashboardController::class, 'tripsOverTime']);

    // Overdue trend over time  (?granularity=day|week|month)
    Route::get('/overdue-trend', [PowerBIDashboardController::class, 'overdueTrend']);

    // ── Pie Chart ────────────────────────────────────────────────────────
    // Long Route vs Short Route distribution
    Route::get('/route-type-distribution', [PowerBIDashboardController::class, 'routeTypeDistribution']);

    // ── Detailed Table ───────────────────────────────────────────────────
    // Full trip records with pagination
    // Optional filters: ?station_id=1  ?route_type=long|short  ?per_page=50
    Route::get('/trip-details', [PowerBIDashboardController::class, 'tripDetails']);

    // ── Reference / Helper ───────────────────────────────────────────────
    // List of all border stations (useful for dropdown filters in Power BI)
    Route::get('/stations', [PowerBIDashboardController::class, 'stations']);
});
