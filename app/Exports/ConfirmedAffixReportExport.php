<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ConfirmedAffixReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $filters;
    protected $modelClass;

    public function __construct($filters = [], $modelClass = null)
    {
        $this->filters = $filters;
        $this->modelClass = $modelClass ?? \App\Models\ConfirmedAffixLog::class;
    }

    public function collection()
    {
        $query = ($this->modelClass)::with([
            'device',
            'route',
            'longRoute',
            'affixedBy'
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

        // Apply date range filter with time support
        if (!empty($this->filters['start_date'])) {
            $startDate = $this->filters['start_date'];
            if (!empty($this->filters['start_time'])) {
                $startDate = $startDate . ' ' . $this->filters['start_time'];
            }
            $query->where('created_at', '>=', $startDate);
        } elseif (!empty($this->filters['start_time'])) {
            $query->whereTime('created_at', '>=', $this->filters['start_time']);
        }

        if (!empty($this->filters['end_date'])) {
            $endDate = $this->filters['end_date'];
            if (!empty($this->filters['end_time'])) {
                $endDate = $endDate . ' ' . $this->filters['end_time'];
            } else {
                $endDate = $endDate . ' 23:59:59';
            }
            $query->where('created_at', '<=', $endDate);
        } elseif (!empty($this->filters['end_time'])) {
            $query->whereTime('created_at', '<=', $this->filters['end_time']);
        }

        if (!empty($this->filters['device_id'])) {
            $deviceId = $this->filters['device_id'];
            $query->whereHas('device', function($q) use ($deviceId) {
                $q->where('device_id', 'LIKE', "%{$deviceId}%");
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

        // Sorting support
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Device ID',
            'SAD/T1',
            'Vehicle Number',
            'Regime',
            'Route',
            'Destination',
            'Agency',
            'Agent Contact',
            'Truck Number',
            'Driver Name',
            'Affixing Date',
            'Affixed By'
        ];
    }

    public function map($row): array
    {
        return [
            $row->device->device_id ?? 'N/A',
            $row->boe,
            $row->vehicle_number,
            $row->regime,
            $row->route->name ?? $row->longRoute->name ?? 'N/A',
            $row->destination,
            $row->agency,
            $row->agent_contact,
            $row->truck_number,
            $row->driver_name,
            $row->affixing_date ? $row->affixing_date->format('Y-m-d H:i:s') : 'N/A',
            $row->affixedBy->name ?? 'N/A',
        ];
    }
}
