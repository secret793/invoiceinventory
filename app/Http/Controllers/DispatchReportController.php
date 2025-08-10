<?php

namespace App\Http\Controllers;

use App\Exports\DispatchReportExport;
use App\Models\DataEntryAssignment;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;

class DispatchReportController extends Controller
{
    public function export(DataEntryAssignment $assignment, Request $request): BinaryFileResponse
    {
        $filters = [
            'device_id' => $request->input('device_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'allocation_point_id' => $request->input('allocation_point_id'),
            'sort_by' => $request->input('sort_by'),
            'sort_direction' => $request->input('sort_direction'),
        ];

        return Excel::download(
            new DispatchReportExport($assignment->id, $filters),
            "dispatch-report-{$assignment->id}-" . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
