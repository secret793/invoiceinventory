<?php

namespace App\Exports;

use App\Services\OverstayDeviceFilterService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class OverstayDevicesExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    /**
     * @var array
     */
    private array $filters;

    /**
     * @var int
     */
    private int $totalDevices = 0;

    /**
     * @var float
     */
    private float $totalAmount = 0;

    /**
     * @var int
     */
    private int $totalDays = 0;

    /**
     * Constructor
     * 
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get data collection
     * 
     * @return Collection
     */
    public function collection(): Collection
    {
        $service = app(OverstayDeviceFilterService::class);
        
        // Get filtered invoices
        $invoices = $service->applyFilters($this->filters)->get();
        
        // Calculate totals
        $this->totalDevices = $invoices->count();
        $this->totalAmount = $invoices->sum('total_amount');
        $this->totalDays = $invoices->sum('overstay_days');
        
        // Map to export format
        return $service->export($this->filters);
    }

    /**
     * Get column headings
     * 
     * @return array
     */
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Device ID',
            'SAD/BOE',
            'Destination',
            'Allocation Point',
            'Overstay Days',
            'Overstay Amount (GMD)',
            'Payment Status',
            'Invoice Date',
            'Created By',
        ];
    }

    /**
     * Register events
     * 
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [$this, 'styles'],
        ];
    }

    /**
     * Apply styling to worksheet
     * 
     * @param AfterSheet $event
     * @return void
     */
    public function styles(AfterSheet $event)
    {
        $worksheet = $event->sheet->getDelegate();
        
        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1e40af'], // Blue
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
        ];
        
        $worksheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Set header row height
        $worksheet->getRowDimension(1)->setRowHeight(25);
        
        // Get total rows (data rows only, without header)
        $totalRows = $worksheet->getHighestRow();
        
        // Apply alternating row colors and borders
        $lightGrayFill = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'f3f4f6'], // Light gray
        ];
        
        $whiteFill = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFFFF'], // White
        ];
        
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'e5e7eb'],
                ],
            ],
        ];
        
        $centerAlignment = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $rightAlignment = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Apply row styling
        for ($row = 2; $row <= $totalRows; $row++) {
            $fill = ($row % 2 == 0) ? $lightGrayFill : $whiteFill;
            
            $worksheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($fill);
            $worksheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($borderStyle);
            
            // Center align most columns
            $worksheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($centerAlignment);
            $worksheet->getStyle('F' . $row . ':H' . $row)->applyFromArray($centerAlignment);
            $worksheet->getStyle('I' . $row . ':J' . $row)->applyFromArray($centerAlignment);
            
            // Right align currency column
            $worksheet->getStyle('G' . $row)->applyFromArray($rightAlignment);
        }
        
        // Add summary section
        $summaryStartRow = $totalRows + 2;
        
        $summaryLabelStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'dbeafe'], // Light blue
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $summaryValueStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'eff6ff'], // Very light blue
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Summary: Total Devices
        $worksheet->setCellValue('E' . $summaryStartRow, 'TOTAL DEVICES:');
        $worksheet->getStyle('E' . $summaryStartRow)->applyFromArray($summaryLabelStyle);
        
        $worksheet->setCellValue('F' . $summaryStartRow, $this->totalDevices);
        $worksheet->getStyle('F' . $summaryStartRow)->applyFromArray($summaryValueStyle);
        
        // Summary: Total Amount
        $summaryAmountRow = $summaryStartRow + 1;
        $worksheet->setCellValue('E' . $summaryAmountRow, 'TOTAL AMOUNT (GMD):');
        $worksheet->getStyle('E' . $summaryAmountRow)->applyFromArray($summaryLabelStyle);
        
        $amountCell = 'F' . $summaryAmountRow;
        $worksheet->setCellValue($amountCell, $this->totalAmount);
        $worksheet->getStyle($amountCell)->applyFromArray($summaryValueStyle);
        $worksheet->getStyle($amountCell)->getNumberFormat()->setFormatCode('"D"#,##0.00');
        
        // Summary: Total Days
        $summaryDaysRow = $summaryAmountRow + 1;
        $worksheet->setCellValue('E' . $summaryDaysRow, 'TOTAL OVERSTAY DAYS:');
        $worksheet->getStyle('E' . $summaryDaysRow)->applyFromArray($summaryLabelStyle);
        
        $worksheet->setCellValue('F' . $summaryDaysRow, $this->totalDays);
        $worksheet->getStyle('F' . $summaryDaysRow)->applyFromArray($summaryValueStyle);
        
        // Set column widths for better readability
        $worksheet->getColumnDimension('A')->setWidth(16); // Invoice Number
        $worksheet->getColumnDimension('B')->setWidth(14); // Device ID
        $worksheet->getColumnDimension('C')->setWidth(14); // SAD/BOE
        $worksheet->getColumnDimension('D')->setWidth(20); // Destination
        $worksheet->getColumnDimension('E')->setWidth(20); // Allocation Point
        $worksheet->getColumnDimension('F')->setWidth(14); // Overstay Days
        $worksheet->getColumnDimension('G')->setWidth(18); // Amount (GMD)
        $worksheet->getColumnDimension('H')->setWidth(14); // Payment Status
        $worksheet->getColumnDimension('I')->setWidth(14); // Invoice Date
        $worksheet->getColumnDimension('J')->setWidth(16); // Created By
    }
}
