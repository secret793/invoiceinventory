<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PowerBIDashboardController extends Controller
{
    /**
     * Parse optional date filters from request.
     * Supports: ?date=2024-01-15  (single day)
     *           ?from=2024-01-01&to=2024-01-31  (date range)
     * When no filter is supplied, returns all-time data.
     */
    private function parseDateRange(Request $request): array
    {
        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
            return [$date->startOfDay(), $date->copy()->endOfDay()];
        }
        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
            $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : null;
            return [$from, $to];
        }
        return [null, null];
    }

    private function applyDateFilter($query, string $column, ?Carbon $from, ?Carbon $to)
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }
        if ($to) {
            $query->where($column, '<=', $to);
        }
        return $query;
    }

    // ─────────────────────────────────────────────
    // 1. KPI SUMMARY
    //    GET /api/powerbi/kpi/summary
    // ─────────────────────────────────────────────
    public function kpiSummary(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        // Total Trips – assign_to_agents table
        $tripsQuery = DB::table('assign_to_agents');
        $this->applyDateFilter($tripsQuery, 'date', $from, $to);
        $totalTrips = $tripsQuery->count();

        // Total Revenue – receipts.total_charge_gmd
        $revenueQuery = DB::table('receipts');
        $this->applyDateFilter($revenueQuery, 'date', $from, $to);
        $totalRevenue = $revenueQuery->sum('total_charge_gmd') ?? 0;

        // Active Trips – monitorings not yet retrieved
        $activeQuery = DB::table('monitorings');
        $this->applyDateFilter($activeQuery, 'date', $from, $to);
        $activeTrips = $activeQuery->count();

        // Average Duration (overstay_days) from device_retrievals
        $durationQuery = DB::table('device_retrievals')->whereNotNull('overstay_days');
        $this->applyDateFilter($durationQuery, 'date', $from, $to);
        $avgDuration = round($durationQuery->avg('overstay_days') ?? 0, 2);

        return response()->json([
            'success' => true,
            'data' => [
                'total_trips'    => $totalTrips,
                'total_revenue'  => round((float) $totalRevenue, 2),
                'active_trips'   => $activeTrips,
                'avg_duration_days' => $avgDuration,
            ],
            'filters' => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 2. KPI OVERDUE
    //    GET /api/powerbi/kpi/overdue
    // ─────────────────────────────────────────────
    public function kpiOverdue(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $base = DB::table('device_retrievals')
            ->where('overstay_days', '>=', 1)
            ->where('retrieval_status', '!=', 'RETRIEVED');

        $this->applyDateFilter($base, 'date', $from, $to);

        $overdueTrips   = (clone $base)->count();
        $overdueRevenue = (clone $base)->sum('overstay_amount') ?? 0;

        // Overdue rate = overdue trips / total active trips
        $allActive = DB::table('monitorings');
        $this->applyDateFilter($allActive, 'date', $from, $to);
        $totalActive  = $allActive->count();
        $overdueRate  = $totalActive > 0
            ? round(($overdueTrips / $totalActive) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'overdue_trips'   => $overdueTrips,
                'overdue_revenue' => round((float) $overdueRevenue, 2),
                'overdue_rate_pct' => $overdueRate,
            ],
            'filters' => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 3. REVENUE BY STATION
    //    GET /api/powerbi/revenue-by-station
    // ─────────────────────────────────────────────
    public function revenueByStation(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $query = DB::table('receipts as r')
            ->join('allocation_points as ap', 'r.allocation_point_id', '=', 'ap.id')
            ->select(
                'ap.id as station_id',
                'ap.name as station_name',
                DB::raw('COALESCE(SUM(r.total_charge_gmd), 0) as total_revenue'),
                DB::raw('COUNT(r.id) as receipt_count')
            )
            ->groupBy('ap.id', 'ap.name')
            ->orderByDesc('total_revenue');

        $this->applyDateFilter($query, 'r.date', $from, $to);

        $rows = $query->get()->map(function ($row) {
            return [
                'station_id'    => $row->station_id,
                'station_name'  => $row->station_name,
                'total_revenue' => round((float) $row->total_revenue, 2),
                'receipt_count' => (int) $row->receipt_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $rows,
            'filters' => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 4. TRIPS BY STATION
    //    GET /api/powerbi/trips-by-station
    // ─────────────────────────────────────────────
    public function tripsByStation(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $query = DB::table('assign_to_agents as a')
            ->join('allocation_points as ap', 'a.allocation_point_id', '=', 'ap.id')
            ->select(
                'ap.id as station_id',
                'ap.name as station_name',
                DB::raw('COUNT(a.id) as total_trips')
            )
            ->groupBy('ap.id', 'ap.name')
            ->orderByDesc('total_trips');

        $this->applyDateFilter($query, 'a.date', $from, $to);

        $rows = $query->get()->map(function ($row) {
            return [
                'station_id'   => $row->station_id,
                'station_name' => $row->station_name,
                'total_trips'  => (int) $row->total_trips,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $rows,
            'filters' => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 5. TRIPS OVER TIME
    //    GET /api/powerbi/trips-over-time
    //    Optional: ?granularity=day|week|month  (default: day)
    // ─────────────────────────────────────────────
    public function tripsOverTime(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);
        $granularity = in_array($request->granularity, ['day', 'week', 'month'])
            ? $request->granularity
            : 'day';

        $dateFormat = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $query = DB::table('assign_to_agents')
            ->select(
                DB::raw("DATE_FORMAT(date, '{$dateFormat}') as period"),
                DB::raw('COUNT(id) as trips')
            )
            ->groupBy('period')
            ->orderBy('period');

        $this->applyDateFilter($query, 'date', $from, $to);

        $rows = $query->get()->map(fn($r) => [
            'period' => $r->period,
            'trips'  => (int) $r->trips,
        ]);

        return response()->json([
            'success'     => true,
            'granularity' => $granularity,
            'data'        => $rows,
            'filters'     => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 6. ROUTE TYPE DISTRIBUTION
    //    GET /api/powerbi/route-type-distribution
    // ─────────────────────────────────────────────
    public function routeTypeDistribution(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $shortQuery = DB::table('assign_to_agents')->whereNotNull('route_id')->whereNull('long_route_id');
        $longQuery  = DB::table('assign_to_agents')->whereNotNull('long_route_id')->whereNull('route_id');

        $this->applyDateFilter($shortQuery, 'date', $from, $to);
        $this->applyDateFilter($longQuery, 'date', $from, $to);

        $shortTrips = $shortQuery->count();
        $longTrips  = $longQuery->count();
        $total      = $shortTrips + $longTrips;

        return response()->json([
            'success' => true,
            'data' => [
                [
                    'route_type'  => 'Short Route',
                    'trip_count'  => $shortTrips,
                    'percentage'  => $total > 0 ? round(($shortTrips / $total) * 100, 2) : 0,
                ],
                [
                    'route_type'  => 'Long Route',
                    'trip_count'  => $longTrips,
                    'percentage'  => $total > 0 ? round(($longTrips / $total) * 100, 2) : 0,
                ],
            ],
            'total_trips' => $total,
            'filters'     => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 7. TRIP DETAILS TABLE
    //    GET /api/powerbi/trip-details
    //    Paginated; optional filters: station_id, route_type, status
    // ─────────────────────────────────────────────
    public function tripDetails(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);
        $perPage = min((int) ($request->per_page ?? 50), 200);

        $query = DB::table('assign_to_agents as a')
            ->leftJoin('allocation_points as ap', 'a.allocation_point_id', '=', 'ap.id')
            ->leftJoin('routes as r', 'a.route_id', '=', 'r.id')
            ->leftJoin('long_routes as lr', 'a.long_route_id', '=', 'lr.id')
            ->leftJoin('receipts as rec', 'a.receipt_id', '=', 'rec.id')
            ->select(
                'a.id',
                'a.date',
                'a.sad_number',
                'a.vehicle_number',
                'a.truck_number',
                'a.driver_name',
                'a.agency',
                'a.destination',
                'a.regime',
                'ap.name as station_name',
                'r.name as short_route_name',
                'lr.name as long_route_name',
                DB::raw("CASE WHEN a.long_route_id IS NOT NULL THEN 'Long Route' ELSE 'Short Route' END as route_type"),
                'rec.total_charge_gmd as revenue',
                'a.manifest_date',
                'a.created_at'
            )
            ->orderByDesc('a.date');

        $this->applyDateFilter($query, 'a.date', $from, $to);

        if ($request->filled('station_id')) {
            $query->where('a.allocation_point_id', $request->station_id);
        }
        if ($request->filled('route_type')) {
            if (strtolower($request->route_type) === 'long') {
                $query->whereNotNull('a.long_route_id');
            } else {
                $query->whereNull('a.long_route_id');
            }
        }

        $paginated = $query->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) {
            return [
                'id'             => $row->id,
                'date'           => $row->date,
                'sad_number'     => $row->sad_number,
                'vehicle_number' => $row->vehicle_number,
                'truck_number'   => $row->truck_number,
                'driver_name'    => $row->driver_name,
                'agency'         => $row->agency,
                'destination'    => $row->destination,
                'regime'         => $row->regime,
                'station_name'   => $row->station_name,
                'route_name'     => $row->long_route_name ?? $row->short_route_name,
                'route_type'     => $row->route_type,
                'revenue'        => $row->revenue !== null ? round((float) $row->revenue, 2) : null,
                'manifest_date'  => $row->manifest_date,
                'created_at'     => $row->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
            'filters' => [
                'from'       => $from?->toDateString(),
                'to'         => $to?->toDateString(),
                'station_id' => $request->station_id,
                'route_type' => $request->route_type,
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 8. OVERDUE TREND OVER TIME
    //    GET /api/powerbi/overdue-trend
    //    Optional: ?granularity=day|week|month
    // ─────────────────────────────────────────────
    public function overdueTrend(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);
        $granularity = in_array($request->granularity, ['day', 'week', 'month'])
            ? $request->granularity
            : 'day';

        $dateFormat = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $query = DB::table('device_retrievals')
            ->where('overstay_days', '>=', 1)
            ->where('retrieval_status', '!=', 'RETRIEVED')
            ->select(
                DB::raw("DATE_FORMAT(date, '{$dateFormat}') as period"),
                DB::raw('COUNT(id) as overdue_trips'),
                DB::raw('COALESCE(SUM(overstay_amount), 0) as overdue_revenue')
            )
            ->groupBy('period')
            ->orderBy('period');

        $this->applyDateFilter($query, 'date', $from, $to);

        $rows = $query->get()->map(fn($r) => [
            'period'          => $r->period,
            'overdue_trips'   => (int) $r->overdue_trips,
            'overdue_revenue' => round((float) $r->overdue_revenue, 2),
        ]);

        return response()->json([
            'success'     => true,
            'granularity' => $granularity,
            'data'        => $rows,
            'filters'     => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 9. OVERDUE BY STATION
    //    GET /api/powerbi/overdue-by-station
    // ─────────────────────────────────────────────
    public function overdueByStation(Request $request)
    {
        [$from, $to] = $this->parseDateRange($request);

        $query = DB::table('device_retrievals as dr')
            ->join('allocation_points as ap', 'dr.allocation_point_id', '=', 'ap.id')
            ->where('dr.overstay_days', '>=', 1)
            ->where('dr.retrieval_status', '!=', 'RETRIEVED')
            ->select(
                'ap.id as station_id',
                'ap.name as station_name',
                DB::raw('COUNT(dr.id) as overdue_trips'),
                DB::raw('COALESCE(SUM(dr.overstay_amount), 0) as overdue_revenue'),
                DB::raw('AVG(dr.overstay_days) as avg_overdue_days')
            )
            ->groupBy('ap.id', 'ap.name')
            ->orderByDesc('overdue_trips');

        $this->applyDateFilter($query, 'dr.date', $from, $to);

        $rows = $query->get()->map(fn($r) => [
            'station_id'       => $r->station_id,
            'station_name'     => $r->station_name,
            'overdue_trips'    => (int) $r->overdue_trips,
            'overdue_revenue'  => round((float) $r->overdue_revenue, 2),
            'avg_overdue_days' => round((float) $r->avg_overdue_days, 1),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $rows,
            'filters' => [
                'from' => $from?->toDateString(),
                'to'   => $to?->toDateString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 10. STATIONS LIST  (helper for filter dropdowns)
    //     GET /api/powerbi/stations
    // ─────────────────────────────────────────────
    public function stations()
    {
        $stations = DB::table('allocation_points')
            ->select('id', 'name', 'location', 'status')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $stations,
        ]);
    }
}
