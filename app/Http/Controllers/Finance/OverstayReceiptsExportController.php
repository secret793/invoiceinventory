<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Exports\OverstayReceiptsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OverstayReceiptsExportController extends Controller
{
    /**
     * Export filtered overstay invoices to Excel
     */
    public function export(Request $request)
    {
        try {
            // Check authorization
            if (!auth()->check() || !auth()->user()->hasRole(['Super Admin', 'Warehouse Manager', 'Finance Officer'])) {
                abort(403, 'Unauthorized to export overstay receipts');
            }

            // Build query with filters
            $query = Invoice::query()
                ->with(['deviceRetrieval', 'deviceRetrieval.destination', 'deviceRetrieval.allocationPoint'])
                ->where(function ($q) {
                    $q->where('overstay_days', '>', 0)
                      ->orWhere('status', 'WAIVED');
                });

            // Apply receipt search
            if (!empty($request->receipt_search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereRaw('LOWER(reference_number) LIKE ?', ['%' . strtolower($request->receipt_search) . '%'])
                      ->orWhereRaw('LOWER(sad_boe) LIKE ?', ['%' . strtolower($request->receipt_search) . '%']);
                });
            }

            // Apply destination filter
            if (!empty($request->destination_id)) {
                $query->whereHas('deviceRetrieval', function ($q) use ($request) {
                    $q->where('destination_id', $request->destination_id);
                });
            }

            // Apply allocation point filter
            if (!empty($request->allocation_point_id)) {
                $query->whereHas('deviceRetrieval', function ($q) use ($request) {
                    $q->where('allocation_point_id', $request->allocation_point_id);
                });
            }

            // Apply date range
            if (!empty($request->start_date)) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if (!empty($request->end_date)) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Apply sorting
            $sortBy = $request->sort_by ?? 'created_at';
            $sortDirection = $request->sort_direction ?? 'desc';
            $query->orderBy($sortBy, $sortDirection);

            // Get filtered data
            $invoices = $query->get();

            // Prepare statistics
            $statistics = [
                'total_invoices' => $invoices->count(),
                'total_trucks' => $invoices->sum(fn($invoice) => $invoice->deviceRetrieval?->moving_trucks ?? 0),
                'total_amount' => $invoices->sum('penalty_amount'),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'generated_by' => auth()->user()->name,
                'generated_at' => now(),
            ];

            // Log the export
            Log::info('Overstay receipts exported', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'total_invoices' => $statistics['total_invoices'],
                'total_trucks' => $statistics['total_trucks'],
                'total_amount' => $statistics['total_amount'],
                'filters' => [
                    'receipt_search' => $request->receipt_search,
                    'destination_id' => $request->destination_id,
                    'allocation_point_id' => $request->allocation_point_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
            ]);

            // Generate filename
            $filename = 'Overstay_Receipts_' 
                . ($request->start_date ? \Carbon\Carbon::parse($request->start_date)->format('Y-m-d') : 'All')
                . '_' 
                . ($request->end_date ? \Carbon\Carbon::parse($request->end_date)->format('Y-m-d') : 'All')
                . '_' 
                . now()->format('Y-m-d-His')
                . '.xlsx';

            // Export using Maatwebsite Excel
            return Excel::download(
                new OverstayReceiptsExport($invoices, $statistics),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Error exporting overstay receipts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to export overstay receipts: ' . $e->getMessage());
        }
    }
}
