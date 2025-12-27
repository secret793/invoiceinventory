<?php

namespace App\Http\Controllers;

use App\Exports\OverstayDevicesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OverstayDevicesExportController extends Controller
{
    /**
     * Export filtered overstay devices to Excel
     * 
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        // Validate request parameters
        $validated = $request->validate([
            'device_id' => 'nullable|string',
            'boe' => 'nullable|string',
            'invoice_number' => 'nullable|string',
            'destination_id' => 'nullable|integer|exists:destinations,id',
            'allocation_point_id' => 'nullable|integer|exists:allocation_points,id',
            'payment_status' => 'nullable|string|in:PP,PD,WAIVED',
            'overstay_amount_min' => 'nullable|numeric|min:0',
            'overstay_amount_max' => 'nullable|numeric|min:0',
            'overstay_days_min' => 'nullable|integer|min:0',
            'overstay_days_max' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|string|in:asc,desc',
        ]);

        // Build filters array
        $filters = [
            'device_id' => $validated['device_id'] ?? null,
            'boe' => $validated['boe'] ?? null,
            'invoice_number' => $validated['invoice_number'] ?? null,
            'destination_id' => $validated['destination_id'] ?? null,
            'allocation_point_id' => $validated['allocation_point_id'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
            'overstay_amount_min' => $validated['overstay_amount_min'] ?? null,
            'overstay_amount_max' => $validated['overstay_amount_max'] ?? null,
            'overstay_days_min' => $validated['overstay_days_min'] ?? null,
            'overstay_days_max' => $validated['overstay_days_max'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Generate filename with timestamp
        $filename = 'overstay-devices-' . now()->format('Y-m-d-His') . '.xlsx';

        // Export and download
        return Excel::download(new OverstayDevicesExport($filters), $filename);
    }
}
