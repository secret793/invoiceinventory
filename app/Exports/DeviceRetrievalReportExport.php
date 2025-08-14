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
        $query = ($this->modelClass)::with([
            'device',
            'route',
            'longRoute',
            'retrievedBy',
            'distributionPoint',
            'allocationPoint' => function($query) {
                $query->withoutGlobalScopes();
            }
        ]);

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

        if (!empty($this->filters['allocation_point_id'])) {
            $query->where('allocation_point_id', $this->filters['allocation_point_id']);
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
            'Allocation Point',
            'Retrieval Status',
            'Action Type',
            'Retrieved By',
            'Retrieval Date',
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
            $log->regime ?? '',
            optional($log->allocationPoint)->name ?? '',
            $log->retrieval_status ?? '',
            $log->action_type ?? '',
            optional($log->retrievedBy)->name ?? '',
            $log->retrieval_date ? $log->retrieval_date->format('Y-m-d H:i:s') : '',
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
