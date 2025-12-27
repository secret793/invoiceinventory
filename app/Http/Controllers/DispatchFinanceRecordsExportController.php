<?php

namespace App\Http\Controllers;

use App\Exports\DispatchFinanceRecordExport;
use App\Models\DispatchFinanceRecord;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DispatchFinanceRecordsExportController extends Controller
{
    /**
     * Export filtered dispatch finance records to Excel
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
            $sortBy = $request->get('sort_by', 'dispatch_date');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Build the filtered query
            $query = DispatchFinanceRecord::query()
                ->with(['receipt.route', 'receipt.longRoute', 'receipt.allocationPoint', 'receipt.destination', 'device', 'creator', 'confirmedAffixed']);

            // Apply filters - Receipt search
            if (!empty($receiptSearch)) {
                $query->whereHas('receipt', function ($q) use ($receiptSearch) {
                    $q->where('receipt_number', 'like', "%{$receiptSearch}%")
                        ->orWhere('sad_number', 'like', "%{$receiptSearch}%");
                });
            }

            // Filter by destination
            if (!empty($destinationId)) {
                $query->whereHas('receipt.destination', function ($q) use ($destinationId) {
                    $q->where('id', $destinationId);
                });
            }

            // Filter by allocation point
            if (!empty($allocationPointId)) {
                $query->whereHas('receipt.allocationPoint', function ($q) use ($allocationPointId) {
                    $q->where('id', $allocationPointId);
                });
            }

            // Filter by date range
            if (!empty($startDate)) {
                $query->whereDate('dispatch_date', '>=', $startDate);
            }

            if (!empty($endDate)) {
                $query->whereDate('dispatch_date', '<=', $endDate);
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortDirection);

            // Get records
            $records = $query->get();

            // Calculate statistics
            $statistics = [
                'total_records' => $records->count(),
                'total_trucks' => $records->sum(function ($record) {
                    return $record->receipt?->moving_trucks ?? 0;
                }),
                'total_amount' => $records->sum('total_amount_gmd') ?? 0,
                'total_short_routes' => $records->pluck('receipt.route_id')->unique()->filter()->count(),
                'total_long_routes' => $records->pluck('receipt.long_route_id')->unique()->filter()->count(),
            ];

            // Generate filename with timestamp
            $filename = 'dispatch-finance-records-' . now()->format('Y-m-d-His') . '.xlsx';

            // Export and download
            return Excel::download(new DispatchFinanceRecordExport($records, $statistics), $filename);
        } catch (\Exception $e) {
            abort(500, 'Export failed: ' . $e->getMessage());
        }
    }
}
