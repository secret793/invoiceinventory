<?php

namespace App\Http\Controllers;

use App\Exports\ConfirmedAffixReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConfirmedAffixReportController extends Controller
{
    /**
     * Export confirmed affix report to Excel
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'device_id' => $request->input('device_id'),
            'boe' => $request->input('boe'),
            'vehicle_number' => $request->input('vehicle_number'),
            'destination' => $request->input('destination'),
            'sort_by' => $request->input('sort_by'),
            'sort_direction' => $request->input('sort_direction'),
        ];

        return Excel::download(
            new ConfirmedAffixReportExport($filters),
            'confirmed-affix-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
