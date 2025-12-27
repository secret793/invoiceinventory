<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DispatchFinanceRecordExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    private Collection $records;
    private ?array $statistics = null;

    public function __construct(Collection $records, ?array $statistics = null)
    {
        $this->records = $records;
        $this->statistics = $statistics;
    }

    public function collection(): Collection
    {
        $data = collect();
        
        // Add statistics section if available
        if ($this->statistics) {
            $data->push(['STATISTICS SUMMARY']);
            $data->push(['']);
            $data->push(['Total Dispatch Records:', $this->statistics['total_records'] ?? 0]);
            $data->push(['Total Trucks:', $this->statistics['total_trucks'] ?? 0]);
            $data->push(['Total Amount (GMD):', $this->statistics['total_amount'] ?? 0]);
            $data->push(['Total Short Routes:', $this->statistics['total_short_routes'] ?? 0]);
            $data->push(['Total Long Routes:', $this->statistics['total_long_routes'] ?? 0]);
            $data->push(['']);
            $data->push(['DISPATCH RECORDS DETAIL']);
            $data->push(['']);
        }
        
        $recordData = $this->records->map(function ($record) {
            return [
                'receipt_number' => $record->receipt?->receipt_number ?? 'N/A',
                'sad_number' => $record->receipt?->sad_number ?? 'N/A',
                'device_id' => $record->device?->device_id ?? 'N/A',
                'dispatch_date' => $record->dispatch_date ? $record->dispatch_date->format('Y-m-d H:i') : 'N/A',
                'dispatched_by' => $record->creator?->name ?? 'N/A',
                'boe' => $record->confirmedAffixed?->boe ?? 'N/A',
                'vehicle_number' => $record->confirmedAffixed?->vehicle_number ?? 'N/A',
                'regime' => $record->confirmedAffixed?->regime ?? 'N/A',
                'route' => $record->receipt?->route?->name ?? 'N/A',
                'long_route' => $record->receipt?->longRoute?->name ?? 'N/A',
                'allocation_point' => $record->receipt?->allocationPoint?->name ?? 'N/A',
                'total_amount_gmd' => number_format($record->total_amount_gmd, 2),
            ];
        });
        
        return $data->concat($recordData);
    }

    public function headings(): array
    {
        return [
            'Receipt Number',
            'SAD/T1',
            'Device ID',
            'Dispatch Date',
            'Dispatched By',
            'BOE',
            'Vehicle',
            'Regime',
            'Route (Short)',
            'Route (Long)',
            'Allocation Point',
            'Total Amount (GMD)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 15,
            'C' => 15,
            'D' => 20,
            'E' => 18,
            'F' => 12,
            'G' => 18,
            'H' => 15,
            'I' => 18,
            'J' => 18,
            'K' => 20,
            'L' => 18,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Get the last row with data
        $lastRow = $sheet->getHighestRow();

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E78'], // Dark blue background
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],

            // Data rows styling
            "A2:L{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'font' => [
                    'size' => 10,
                ],
            ],

            // Alternate row colors
            "A2:L{$lastRow}" => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2'], // Light gray
                ],
            ],
        ];
    }
}
