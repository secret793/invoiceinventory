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

class DeviceRetrievalReport2Export implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $filters;
    protected $modelClass;

    public function __construct($filters = [], $modelClass = null)
    {
        $this->filters = $filters;
        $this->modelClass = $modelClass ?? \App\Models\DeviceRetrievalReport2::class;
    }

    public function collection()
    {
        // Base query with global scope for automatic destination/role filtering
        // Global scope handles: Super Admin/Warehouse Manager (see all), 
        // Retrieval Officers (destination permissions), Finance Officers (overstay >= 2)
        $query = ($this->modelClass)::query()->with([
            'device',
            'retrievedBy',
            'returnedBy',
            'route',
            'longRoute',
            'allocationPoint' => function($query) {
                $query->withoutGlobalScopes();
            }
        ]);

        // Report #2 requires retrieval_status selection (primary filter)
        if (!empty($this->filters['retrieval_status'])) {
            if ($this->filters['retrieval_status'] === 'ALL_STATUS') {
                $query->whereIn('retrieval_status', ['RETRIEVED', 'RETURNED']);
            } else {
                $query->where('retrieval_status', $this->filters['retrieval_status']);
            }
            
            // Only apply date/time filters if retrieval status is selected
            if (!empty($this->filters['start_date']) || !empty($this->filters['end_date'])) {
                $startDateTime = null;
                $endDateTime = null;

                if (!empty($this->filters['start_date'])) {
                    $startDateTime = $this->filters['start_date'];
                    if (!empty($this->filters['start_time'])) {
                        $startDateTime .= ' ' . $this->filters['start_time'] . ':00';
                    } else {
                        $startDateTime .= ' 00:00:00';
                    }
                }

                if (!empty($this->filters['end_date'])) {
                    $endDateTime = $this->filters['end_date'];
                    if (!empty($this->filters['end_time'])) {
                        $endDateTime .= ' ' . $this->filters['end_time'] . ':00';
                    } else {
                        $endDateTime .= ' 23:59:59';
                    }
                }

                // Handle date filtering based on status
                $status = $this->filters['retrieval_status'] ?? null;
                if ($status === 'ALL_STATUS') {
                    // For ALL_STATUS, include records that were RETRIEVED in the range OR RETURNED in the range
                    // This handles cases where devices are retrieved in one date range and returned in another
                    $query->where(function($q) use ($startDateTime, $endDateTime) {
                        // Check retrieval_date for RETRIEVED records
                        $q->where(function($subQ) use ($startDateTime, $endDateTime) {
                            $subQ->where('retrieval_status', 'RETRIEVED')
                                 ->when($startDateTime, fn ($q) => $q->where('retrieval_date', '>=', $startDateTime))
                                 ->when($endDateTime, fn ($q) => $q->where('retrieval_date', '<=', $endDateTime));
                        })
                        // OR check returned_at for RETURNED records
                        ->orWhere(function($subQ) use ($startDateTime, $endDateTime) {
                            $subQ->where('retrieval_status', 'RETURNED')
                                 ->when($startDateTime, fn ($q) => $q->where('returned_at', '>=', $startDateTime))
                                 ->when($endDateTime, fn ($q) => $q->where('returned_at', '<=', $endDateTime));
                        })
                        // OR include RETRIEVED records with any return date in the range
                        ->orWhere(function($subQ) use ($startDateTime, $endDateTime) {
                            $subQ->where('retrieval_status', 'RETURNED')
                                 ->when($startDateTime, fn ($q) => $q->where('retrieval_date', '>=', $startDateTime))
                                 ->when($endDateTime, fn ($q) => $q->where('retrieval_date', '<=', $endDateTime));
                        });
                    });
                } else {
                    // For specific status (RETRIEVED or RETURNED), apply date filter to appropriate column
                    // RETRIEVED: filter by retrieval_date
                    // RETURNED: filter by returned_at (but include all devices regardless of retrieval_date)
                    $dateColumn = match($status) {
                        'RETRIEVED' => 'retrieval_date',
                        'RETURNED' => 'returned_at',
                        default => 'created_at'
                    };
                    
                    $query->when($startDateTime, fn ($query) => $query->where($dateColumn, '>=', $startDateTime))
                          ->when($endDateTime, fn ($query) => $query->where($dateColumn, '<=', $endDateTime));
                }
            }
        } else {
            // If no retrieval status selected, return empty result
            $query->whereRaw('1 = 0');
        }

        // Apply other filters (simplified for Report #2 model with device_full_id)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('device_full_id', 'like', "%{$search}%")
                  ->orWhere('boe', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        // Individual field filters
        if (!empty($this->filters['device_id'])) {
            $deviceId = $this->filters['device_id'];
            $query->where('device_full_id', 'like', "%{$deviceId}%");
        }

        if (!empty($this->filters['boe'])) {
            $query->where('boe', 'like', "%{$this->filters['boe']}%");
        }

        if (!empty($this->filters['vehicle_number'])) {
            $query->where('vehicle_number', 'like', "%{$this->filters['vehicle_number']}%");
        }

        if (!empty($this->filters['action_type'])) {
            $query->where('action_type', $this->filters['action_type']);
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
            'Device ID',
            'BOE',
            'Vehicle Number',
            'Route',
            'Regime',
            'Destination',
            'Allocation Point',
            'Retrieval Status',
            'Action Type',
            'Retrieved By',
            'Returned By',
            'Retrieval Date',
            'Returned At',
            'Overstay Days',
            'Overstay Amount (GMD)',
        ];
    }

    public function map($record): array
    {
        // Determine which route to display (Long Route takes precedence if present)
        $routeDisplay = '';
        if ($record->longRoute) {
            $routeDisplay = 'Long Route: ' . $record->longRoute->name;
        } elseif ($record->route) {
            $routeDisplay = $record->route->name;
        } else {
            $routeDisplay = 'N/A';
        }

        return [
            $record->device_full_id ?: 'N/A',
            $record->boe ?: 'N/A',
            $record->vehicle_number ?: 'N/A',
            $routeDisplay,
            $record->regime ?: 'N/A',
            $record->destination ?: 'N/A',
            $record->allocationPoint ? $record->allocationPoint->name : 'N/A',
            $record->retrieval_status ?: 'N/A',
            $record->action_type ?: 'N/A',
            $record->retrievedBy ? $record->retrievedBy->name : 'N/A',
            $record->returnedBy ? $record->returnedBy->name : 'N/A',
            $record->retrieval_date ? $record->retrieval_date->format('Y-m-d H:i:s') : 'N/A',
            $record->returned_at ? $record->returned_at->format('Y-m-d H:i:s') : 'N/A',
            $record->overstay_days ?: 0,
            number_format($record->overstay_amount ?: 0, 2),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the total count of records
                $totalCount = $this->collection()->count();

                // Style the header row (now has 14 columns: A to N)
                $event->sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F59E0B'], // Amber color for Report #2
                    ],
                ]);

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
                            'rgb' => 'FEF3C7', // Light amber background for Report #2
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
