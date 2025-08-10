<?php

namespace App\Http\Controllers;

use App\Exports\DeviceRetrievalReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeviceRetrievalReportController extends Controller
{
    /**
     * Export device retrieval report to Excel
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
            'retrieval_status' => $request->input('retrieval_status'),
            'action_type' => $request->input('action_type'),
            'sort_by' => $request->input('sort_by'),
            'sort_direction' => $request->input('sort_direction'),
        ];

        return Excel::download(
            new DeviceRetrievalReportExport($filters),
            'device-retrieval-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
