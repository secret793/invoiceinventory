<?php

namespace App\Http\Controllers;

use App\Exports\GeneratedReceiptsExport;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneratedReceiptsExportController extends Controller
{
    /**
     * Export filtered generated receipts to Excel
     * 
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        try {
            // Get all filters from request
            $receiptSearch = $request->get('receipt_search');
            $destinationId = $request->get('destination_id');
            $allocationPointId = $request->get('allocation_point_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Build the filtered query
            $query = Receipt::query()
                ->whereNotNull('generated_by_user')
                ->with(['route', 'longRoute', 'destination', 'allocationPoint', 'generatedByUser']);

            // Apply filters
            if (!empty($receiptSearch)) {
                $query->where(function ($q) use ($receiptSearch) {
                    $q->where('receipt_number', 'like', "%{$receiptSearch}%")
                        ->orWhere('sad_number', 'like', "%{$receiptSearch}%");
                });
            }

            if (!empty($destinationId)) {
                $query->where('destination_id', $destinationId);
            }

            if (!empty($allocationPointId)) {
                $query->where('allocation_point_id', $allocationPointId);
            }

            if (!empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if (!empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortDirection);

            // Get receipts
            $receipts = $query->get();

            // Calculate statistics
            $statistics = [
                'total_receipts' => $receipts->count(),
                'total_trucks' => $receipts->sum('moving_trucks') ?? 0,
                'total_amount' => $receipts->sum('total_charge_gmd') ?? 0,
                'total_short_routes' => $receipts->where('route_id', '!=', null)->pluck('route_id')->unique()->count(),
                'total_long_routes' => $receipts->where('long_route_id', '!=', null)->pluck('long_route_id')->unique()->count(),
            ];

            // Generate filename with timestamp
            $filename = 'generated-receipts-' . now()->format('Y-m-d-His') . '.xlsx';

            // Export and download
            return Excel::download(new GeneratedReceiptsExport($receipts, $statistics), $filename);
        } catch (\Exception $e) {
            abort(500, 'Export failed: ' . $e->getMessage());
        }
    }
}
