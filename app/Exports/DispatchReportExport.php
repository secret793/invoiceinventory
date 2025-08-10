<?php

namespace App\Exports;

use App\Models\DispatchLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DispatchReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $assignmentId;
    protected $filters;

    public function __construct($assignmentId, $filters = [])
    {
        $this->assignmentId = $assignmentId;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DispatchLog::with([
                'device',
                'dispatcher',
                'device.confirmedAffixed',
                'device.confirmedAffixed.route',
                'device.confirmedAffixed.longRoute'
            ])
            ->where('data_entry_assignment_id', $this->assignmentId);

        // Apply device ID filter
        if (!empty($this->filters['device_id'])) {
            $deviceId = $this->filters['device_id'];
            $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'LIKE', "%{$deviceId}%");
            });
        }

        // Apply date and time range filter
        if (!empty($this->filters['start_date'])) {
            $startDate = $this->filters['start_date'];
            // If start time is provided, combine with date
            if (!empty($this->filters['start_time'])) {
                $startDate = $startDate . ' ' . $this->filters['start_time'];
            }
            $query->where('dispatched_at', '>=', $startDate);
        } elseif (!empty($this->filters['start_time'])) {
            // If only time is provided, use current date with the specified time
            $query->whereTime('dispatched_at', '>=', $this->filters['start_time']);
        }

        if (!empty($this->filters['end_date'])) {
            $endDate = $this->filters['end_date'];
            // If end time is provided, combine with date
            if (!empty($this->filters['end_time'])) {
                $endDate = $endDate . ' ' . $this->filters['end_time'];
            } else {
                // If no end time, set to end of day
                $endDate = $endDate . ' 23:59:59';
            }
            $query->where('dispatched_at', '<=', $endDate);
        } elseif (!empty($this->filters['end_time'])) {
            // If only time is provided, use current date with the specified time
            $query->whereTime('dispatched_at', '<=', $this->filters['end_time']);
        }

        // Apply allocation point filter
        if (!empty($this->filters['allocation_point_id'])) {
            $query->whereHas('device', function($q) {
                $q->whereHas('confirmedAffixed', function($q) {
                    $q->where('allocation_point_id', $this->filters['allocation_point_id']);
                });
            });
        }

        // Apply sorting (same as modal)
        $sortBy = $this->filters['sort_by'] ?? 'dispatched_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Device ID',
            'Dispatched At',
            'Dispatched By',
            'BOE #',
            'Vehicle #',
            'Regime',
            'Route',
            'Destination'
        ];
    }

    public function map($log): array
    {
        return [
            $log->device->device_id ?? 'N/A',
            $log->dispatched_at?->format('M d, Y h:i A'),
            $log->dispatcher->name ?? 'N/A',
            $log->details['boe'] ?? 'N/A',
            $log->details['vehicle_number'] ?? 'N/A',
            $log->device->confirmedAffixed->regime ?? $log->details['regime'] ?? 'N/A',
            $log->device->confirmedAffixed->route->name ?? $log->device->confirmedAffixed->longRoute->name ?? $log->details['route'] ?? 'N/A',
            $log->details['destination'] ?? 'N/A',
        ];
    }
}
