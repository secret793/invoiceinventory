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

class DeviceRetrievalReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
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
        $query = ($this->modelClass)::withoutGlobalScopes()->with([
            'device',
            'route',
            'longRoute',
            'retrievedBy',
            'distributionPoint',
            'allocationPoint' => function($query) {
                $query->withoutGlobalScopes();
            }
        ]);

        // Apply destination-based permissions
        $user = auth()->user();
        if ($user && !$user->hasRole(['Super Admin', 'Warehouse Manager'])) {
            // Get user's permissions
            $permissions = $user->permissions->pluck('name')->toArray();
            
            // Filter for destination permissions (assuming they follow a pattern like 'view_destination_soma')
            $destinationPermissions = array_filter($permissions, function ($permission) {
                return \Illuminate\Support\Str::startsWith($permission, 'view_destination_');
            });

            if (!empty($destinationPermissions)) {
                $query->where(function ($q) use ($destinationPermissions) {
                    foreach ($destinationPermissions as $permission) {
                        $destination = \Illuminate\Support\Str::after($permission, 'view_destination_');
                        $q->orWhere('destination', 'LIKE', "%{$destination}%");
                    }
                });
            } else {
                // If no destination permissions, check allocation points as fallback
                $allocationPointPermissions = array_filter($permissions, function ($permission) {
                    return \Illuminate\Support\Str::startsWith($permission, 'view_allocationpoint_');
                });

                if (!empty($allocationPointPermissions)) {
                    try {
                        $allocationPoints = collect(\DB::table('allocation_points')->get());
                        $matchingPoints = $allocationPoints->filter(function($point) use ($allocationPointPermissions) {
                            foreach ($allocationPointPermissions as $permission) {
                                $permissionName = \Illuminate\Support\Str::after($permission, 'view_allocationpoint_');
                                if (str_contains(strtolower($point->name), strtolower($permissionName))) {
                                    return true;
                                }
                            }
                            return false;
                        });

                        $allocationPointIds = $matchingPoints->pluck('id')->toArray();
                        if (!empty($allocationPointIds)) {
                            $query->whereIn('allocation_point_id', $allocationPointIds);
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    } catch (\Exception $e) {
                        \Log::error('DeviceRetrievalReportExport: Error filtering by allocation points', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id
                        ]);
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // Note: Permission filtering is now handled by the DeviceRetrievalLog global scope
        // which filters by destination permissions for Retrieval Officers

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

        if (isset($this->filters['destination']) && trim($this->filters['destination']) !== '') {
            $destination = trim($this->filters['destination']);
            
            // Apply case-insensitive destination filtering
            $query->where('destination', 'LIKE', "%{$destination}%");
            
            // Log destination filtering
            \Log::info('DeviceRetrievalReportExport: Destination filter applied', [
                'user_id' => auth()->id(),
                'destination_filter' => $destination,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
        }

        if (!empty($this->filters['allocation_point_id'])) {
            $query->where('allocation_point_id', $this->filters['allocation_point_id']);
        }

        if (!empty($this->filters['retrieval_status'])) {
            $query->where('retrieval_status', $this->filters['retrieval_status']);
        }

        if (!empty($this->filters['action_type'])) {
            $query->where('action_type', $this->filters['action_type']);
        }

        // Determine which date field to use based on retrieval status
        $dateField = 'retrieval_date'; // default to retrieval_date
        if (!empty($this->filters['retrieval_status'])) {
            if (strtoupper($this->filters['retrieval_status']) === 'RETURNED') {
                $dateField = 'returned_at';
            }
        }

        // Log which date field is being used
        \Log::info('DeviceRetrievalReportExport: Date field selection', [
            'selected_date_field' => $dateField,
            'retrieval_status' => $this->filters['retrieval_status'] ?? 'not_set'
        ]);

        // Apply date and time filters
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $startDate = $this->filters['start_date'];
            $endDate = $this->filters['end_date'];

            if (!empty($this->filters['start_time']) && !empty($this->filters['end_time'])) {
                $startDateTime = $startDate . ' ' . $this->filters['start_time'];
                $endDateTime = $endDate . ' ' . $this->filters['end_time'];
                $query->whereBetween($dateField, [$startDateTime, $endDateTime]);
            } else {
                $query->whereDate($dateField, '>=', $startDate)
                      ->whereDate($dateField, '<=', $endDate);
            }
        } elseif (!empty($this->filters['start_date'])) {
            if (!empty($this->filters['start_time'])) {
                $startDateTime = $this->filters['start_date'] . ' ' . $this->filters['start_time'];
                $query->where($dateField, '>=', $startDateTime);
            } else {
                $query->whereDate($dateField, '>=', $this->filters['start_date']);
            }
        } elseif (!empty($this->filters['end_date'])) {
            if (!empty($this->filters['end_time'])) {
                $endDateTime = $this->filters['end_date'] . ' ' . $this->filters['end_time'];
                $query->where($dateField, '<=', $endDateTime);
            } else {
                $query->whereDate($dateField, '<=', $this->filters['end_date']);
            }
        }


        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Debug logging
        $finalQuery = $query->toSql();
        $bindings = $query->getBindings();
        $results = $query->get();

        \Log::info('DeviceRetrievalReportExport: Query debug', [
            'user_id' => $user?->id,
            'user_roles' => $user ? $user->roles->pluck('name')->toArray() : [],
            'user_permissions' => $user ? $user->permissions->pluck('name')->toArray() : [],
            'sql' => $finalQuery,
            'bindings' => $bindings,
            'result_count' => $results->count(),
            'filters' => $this->filters
        ]);

        return $results;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Device ID',
            'BOE',
            'Vehicle Number',
            'Destination',
            'Regime',
            'Allocation Point',
            'Retrieval Status',
            'Action Type',
            'Retrieved By',
            'Retrieval Date',
            'Returned At',
            'Overstay Days',
            'Overstay Amount',
            'Payment Status',
            'Route',
            'Long Route',
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
            $log->destination ?? '',
            $log->regime ?? '',
            optional($log->allocationPoint)->name ?? '',
            $log->retrieval_status ?? '',
            $log->action_type ?? '',
            optional($log->retrievedBy)->name ?? '',
            $log->retrieval_date ? $log->retrieval_date->format('Y-m-d H:i:s') : '',
            $log->returned_at ? $log->returned_at->format('Y-m-d H:i:s') : '',
            $log->overstay_days ?? 0,
            $log->overstay_amount ?? 0,
            $log->payment_status ?? '',
            optional($log->route)->name ?? '',
            optional($log->longRoute)->name ?? '',
            $log->agency ?? '',
            $log->agent_contact ?? '',
            $log->truck_number ?? '',
            $log->driver_name ?? '',
            $log->manifest_date ? $log->manifest_date->format('Y-m-d') : '',
            $log->affixing_date ? $log->affixing_date->format('Y-m-d') : '',
            $log->note ?? '',
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
