<?php

namespace App\Http\Controllers;

use App\Exports\GeneratedReceiptsPDFExport;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GeneratedReceiptsPDFExportController extends Controller
{
    public function export(Request $request): Response
    {
        $receiptSearch     = $request->get('receipt_search');
        $destinationId     = $request->get('destination_id');
        $allocationPointId = $request->get('allocation_point_id');
        $startDate         = $request->get('start_date');
        $endDate           = $request->get('end_date');
        $sortBy            = $request->get('sort_by', 'created_at');
        $sortDirection     = $request->get('sort_direction', 'desc');

        $query = Receipt::query()
            ->whereNotNull('generated_by_user')
            ->with(['route', 'longRoute', 'destination', 'allocationPoint', 'generatedByUser']);

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

        $query->orderBy($sortBy, $sortDirection);

        $receipts = $query->get();

        $statistics = [
            'total_receipts'    => $receipts->count(),
            'total_trucks'      => $receipts->sum('moving_trucks') ?? 0,
            'total_amount'      => $receipts->sum('total_charge_gmd') ?? 0,
            'total_short_routes' => $receipts->whereNotNull('route_id')->pluck('route_id')->unique()->count(),
            'total_long_routes' => $receipts->whereNotNull('long_route_id')->pluck('long_route_id')->unique()->count(),
        ];

        $filters = array_filter(compact(
            'receiptSearch', 'destinationId', 'allocationPointId', 'startDate', 'endDate'
        ));

        $pdf      = (new GeneratedReceiptsPDFExport($receipts, $statistics, $filters))->generate();
        $filename = 'generated-receipts-' . now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }
}
