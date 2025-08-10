<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DeviceRetrievalReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;
    protected $modelClass;

    public function __construct($filters = [], $modelClass = null)
    {
        $this->filters = $filters;
        $this->modelClass = $modelClass ?? \App\Models\DeviceRetrievalLog::class;
    }

    public function collection()
    {
        $query = ($this->modelClass)::with([
            'device',
            'route',
            'longRoute',
            'retrievedBy',
            'distributionPoint',
            'allocationPoint'
        ]);

        // Apply allocation point permission filtering
        $user = auth()->user();
        if (!$user->hasRole(['Super Admin', 'Warehouse Manager'])) {
            $userAllocationPoints = $user->allocationPoints->pluck('id')->toArray();
            if (!empty($userAllocationPoints)) {
                $query->whereIn('allocation_point_id', $userAllocationPoints);
            } else {
                // If user has no allocation points assigned, show no records
                $query->whereRaw('1 = 0');
            }
        }

        // Apply general search filter (searches device_id, boe, vehicle_number)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('device', function($deviceQuery) use ($search) {
                    $deviceQuery->where('device_id', 'LIKE', "%{$search}%");
                })
                ->orWhere('boe', 'LIKE', "%{$search}%")
                ->orWhere('vehicle_number', 'LIKE', "%{$search}%");
            });
        }

        // Apply individual filters
        if (!empty($this->filters['device_id'])) {
            $query->whereHas('device', function($deviceQuery) {
                $deviceQuery->where('device_id', 'LIKE', "%{$this->filters['device_id']}%");
            });
        }

        if (!empty($this->filters['boe'])) {
            $query->where('boe', 'LIKE', "%{$this->filters['boe']}%");
        }

        if (!empty($this->filters['vehicle_number'])) {
            $query->where('vehicle_number', 'LIKE', "%{$this->filters['vehicle_number']}%");
        }

        if (!empty($this->filters['destination'])) {
            $query->where('destination', 'LIKE', "%{$this->filters['destination']}%");
        }

        if (!empty($this->filters['retrieval_status'])) {
            $query->where('retrieval_status', $this->filters['retrieval_status']);
        }

        if (!empty($this->filters['action_type'])) {
            $query->where('action_type', $this->filters['action_type']);
        }

        // Apply date and time filters
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $startDate = $this->filters['start_date'];
            $endDate = $this->filters['end_date'];

            if (!empty($this->filters['start_time']) && !empty($this->filters['end_time'])) {
                $startDateTime = $startDate . ' ' . $this->filters['start_time'];
                $endDateTime = $endDate . ' ' . $this->filters['end_time'];
                $query->whereBetween('created_at', [$startDateTime, $endDateTime]);
            } else {
                $query->whereDate('created_at', '>=', $startDate)
                      ->whereDate('created_at', '<=', $endDate);
            }
        } elseif (!empty($this->filters['start_date'])) {
            if (!empty($this->filters['start_time'])) {
                $startDateTime = $this->filters['start_date'] . ' ' . $this->filters['start_time'];
                $query->where('created_at', '>=', $startDateTime);
            } else {
                $query->whereDate('created_at', '>=', $this->filters['start_date']);
            }
        } elseif (!empty($this->filters['end_date'])) {
            if (!empty($this->filters['end_time'])) {
                $endDateTime = $this->filters['end_date'] . ' ' . $this->filters['end_time'];
                $query->where('created_at', '<=', $endDateTime);
            } else {
                $query->whereDate('created_at', '<=', $this->filters['end_date']);
            }
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Device ID',
            'BOE',
            'Vehicle Number',
            'Regime',
            'Destination',
            'Retrieval Status',
            'Action Type',
            'Retrieved By',
            'Retrieval Date',
            'Overstay Days',
            'Overstay Amount',
            'Payment Status',
            'Route',
            'Long Route',
            'Distribution Point',
            'Allocation Point',
            'Agency',
            'Agent Contact',
            'Truck Number',
            'Driver Name',
            'Manifest Date',
            'Affixing Date',
            'Note',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
            $log->device->device_id ?? '',
            $log->boe ?? '',
            $log->vehicle_number ?? '',
            $log->regime ?? '',
            $log->destination ?? '',
            $log->retrieval_status ?? '',
            $log->action_type ?? '',
            $log->retrievedBy->name ?? '',
            $log->retrieval_date ? $log->retrieval_date->format('Y-m-d H:i:s') : '',
            $log->overstay_days ?? 0,
            $log->overstay_amount ?? 0,
            $log->payment_status ?? '',
            $log->route->route_name ?? '',
            $log->longRoute->long_route_name ?? '',
            $log->distributionPoint->name ?? '',
            $log->allocationPoint->name ?? '',
            $log->agency ?? '',
            $log->agent_contact ?? '',
            $log->truck_number ?? '',
            $log->driver_name ?? '',
            $log->manifest_date ? $log->manifest_date->format('Y-m-d') : '',
            $log->affixing_date ? $log->affixing_date->format('Y-m-d') : '',
            $log->note ?? '',
        ];
    }
}
