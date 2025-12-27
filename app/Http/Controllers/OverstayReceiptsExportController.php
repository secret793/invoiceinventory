<?php

namespace App\Http\Controllers;

use App\Exports\OverstayReceiptExport;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OverstayReceiptsExportController extends Controller
{
    public function export(Request $request)
    {
        // Get filters from URL params
        $referenceSearch = $request->get('reference_search');
        $statusFilter = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Build query (SAME as Livewire)
        $query = Invoice::query();

        if (!empty($referenceSearch)) {
            $query->where(function ($q) use ($referenceSearch) {
                $q->where('reference_number', 'LIKE', "%{$referenceSearch}%")
                  ->orWhere('sad_boe', 'LIKE', "%{$referenceSearch}%")
                  ->orWhere('device_number', 'LIKE', "%{$referenceSearch}%");
            });
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($startDate)) {
            $query->whereDate('reference_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('reference_date', '<=', $endDate);
        }

        $query->orderBy($sortBy, $sortDirection);
        $records = $query->get();

        // Calculate statistics
        $statistics = [
            'total_records' => $records->count(),
            'total_amount' => $records->sum('total_amount') ?? 0,
            'paid_count' => $records->where('status', 'PD')->count(),
            'pending_count' => $records->where('status', 'PP')->count(),
            'waived_count' => $records->where('status', 'WAIVED')->count(),
        ];

        // Generate filename
        $filename = 'overstay-receipts-' . now()->format('Y-m-d-His') . '.xlsx';

        // Download
        return Excel::download(new OverstayReceiptExport($records, $statistics), $filename);
    }
}
