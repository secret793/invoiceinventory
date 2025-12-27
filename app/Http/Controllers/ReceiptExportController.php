<?php

namespace App\Http\Controllers;

use App\Exports\ReceiptExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceiptExportController extends Controller
{
    /**
     * Export filtered receipts to Excel
     * 
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        // Validate request
        $validated = $request->validate([
            'allocation_point_id' => 'required|integer|exists:allocation_points,id',
            'receipt_number' => 'nullable|string',
            'destination_id' => 'nullable|integer|exists:destinations,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'start_time' => 'nullable|date_format:H:i',
            'end_date' => 'nullable|date_format:Y-m-d',
            'end_time' => 'nullable|date_format:H:i',
            'sort_by' => 'nullable|string|in:date,receipt_number,total_charge_gmd,moving_trucks',
            'sort_direction' => 'nullable|string|in:asc,desc',
        ]);

        // Build filters array
        $filters = [
            'allocation_point_id' => $validated['allocation_point_id'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'destination_id' => $validated['destination_id'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'date',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Generate filename with timestamp
        $filename = 'receipts-' . now()->format('Y-m-d-His') . '.xlsx';

        // Export and download
        return Excel::download(new ReceiptExport($filters), $filename);
    }
}
