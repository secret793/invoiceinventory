<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmedAffixReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
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
            'affixedBy',
            'allocationPoint'
        ]);

        // Apply allocation point permission filtering (same logic as ConfirmedAffixed model)
        $user = auth()->user();
        if ($user && !$user->hasRole(['Super Admin', 'Warehouse Manager'])) {
            // For Retrieval Officer and Affixing Officer, filter by allocation point permissions
            if ($user->hasRole(['Retrieval Officer', 'Affixing Officer'])) {
                // Get all permissions starting with 'view_allocationpoint_'
                $permissions = $user->permissions->pluck('name')->toArray();
                $allocationPointPermissions = array_filter($permissions, function ($permission) {
                    return \Illuminate\Support\Str::startsWith($permission, 'view_allocationpoint_');
                });

                // Extract allocation point names from permissions
                $allocationPointNames = array_map(function ($permission) {
                    return \Illuminate\Support\Str::after($permission, 'view_allocationpoint_');
                }, $allocationPointPermissions);

                if (!empty($allocationPointNames)) {
                    try {
                        // Get allocation points directly with raw query for reliability
                        $allocationPoints = collect(\DB::table('allocation_points')->get())
                            ->map(function($item) {
                                return (object)[
                                    'id' => $item->id,
                                    'name' => $item->name,
                                    'location' => $item->location,
                                    'status' => $item->status
                                ];
                            });

                        // Find matching allocation points by name (case insensitive)
                        $matchingPoints = $allocationPoints->filter(function($point) use ($allocationPointNames) {
                            $pointName = strtolower($point->name);
                            foreach ($allocationPointNames as $searchName) {
                                if (str_contains($pointName, strtolower($searchName))) {
                                    return true;
                                }
                            }
                            return false;
                        });

                        $allocationPointIds = $matchingPoints->pluck('id')->toArray();
                        $allocationPointIds = array_unique($allocationPointIds);

                        if (!empty($allocationPointIds)) {
                            $query->whereIn('allocation_point_id', $allocationPointIds);
                        } else {
                            // Show nothing if no matching allocation points
                            $query->whereRaw('1 = 0');
                        }
                    } catch (\Exception $e) {
                        \Log::error('ConfirmedAffixReportExport: Error filtering by allocation points', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id
                        ]);
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    // Show nothing if no permissions
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Default: show nothing for other roles
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
        if (!empty($this->filters['allocation_point_id'])) {
            $query->where('allocation_point_id', $this->filters['allocation_point_id']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
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
            'BOE/SAD',
            'Vehicle Number',
            'Regime',
            'Route',
            'Destination',
            'Manifest Date',
            'Agency',
            'Agent Contact',
            'Truck Number',
            'Driver Name',
            'Affixing Date',
            'Status',
            'Allocation Point',
            'Affixed By',
            'Created At'
        ];
    }

    public function map($row): array
    {
        return [
            $row->device->device_id ?? 'N/A',
            $row->boe ?? 'N/A',
            $row->vehicle_number ?? 'N/A',
            $row->regime ?? 'N/A',
            $row->route->name ?? $row->longRoute->name ?? 'N/A',
            $row->destination ?? 'N/A',
            $row->manifest_date ? $row->manifest_date->format('Y-m-d') : 'N/A',
            $row->agency ?? 'N/A',
            $row->agent_contact ?? 'N/A',
            $row->truck_number ?? 'N/A',
            $row->driver_name ?? 'N/A',
            $row->affixing_date ? $row->affixing_date->format('Y-m-d H:i:s') : 'N/A',
            $row->status ?? 'N/A',
            optional($row->allocationPoint)->name ?? 'N/A',
            $row->affixedBy ? $row->affixedBy->name : 'N/A',
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the total count of records
                $totalCount = $this->collection()->count();

                // Find the last row with data
                $lastRow = $sheet->getHighestRow();

                // Add total count 2 rows below the data
                $totalRow = $lastRow + 2;

                // Add the total count in the first column
                $sheet->setCellValue('A' . $totalRow, 'Total Devices:');
                $sheet->setCellValue('B' . $totalRow, $totalCount);

                // Style the total row
                $sheet->getStyle('A' . $totalRow . ':B' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'E8F4FD',
                        ],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Auto-size all columns
                foreach (range('A', $sheet->getHighestColumn()) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
