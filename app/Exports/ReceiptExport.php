<?php

namespace App\Exports;

use App\Services\ReceiptFilterService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReceiptExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected array $filters;
    protected ReceiptFilterService $filterService;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
        $this->filterService = app(ReceiptFilterService::class);
    }

    /**
     * Get the collection for the export
     */
    public function collection()
    {
        return $this->filterService->export($this->filters);
    }

    /**
     * Define the headings for the export
     */
    public function headings(): array
    {
        return [
            'Receipt Number',
            'SAD/T1',
            'Route (Short)',
            'Route (Long)',
            'Destination',
            'Date & Time',
            'Trucks',
            'Available Usage',
            'Total Charged (GMD)',
        ];
    }

    /**
     * Register events for styling and statistics
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Style header row
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '3B82F6'], // Blue
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ];

                $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

                // Auto-size columns
                $sheet->getColumnDimension('A')->setWidth(18); // Receipt Number
                $sheet->getColumnDimension('B')->setWidth(15); // SAD/T1
                $sheet->getColumnDimension('C')->setWidth(18); // Route Short
                $sheet->getColumnDimension('D')->setWidth(18); // Route Long
                $sheet->getColumnDimension('E')->setWidth(20); // Destination
                $sheet->getColumnDimension('F')->setWidth(18); // Date & Time
                $sheet->getColumnDimension('G')->setWidth(10); // Trucks
                $sheet->getColumnDimension('H')->setWidth(16); // Available Usage
                $sheet->getColumnDimension('I')->setWidth(18); // Total Charged

                // Get the last row with data
                $lastRow = $sheet->getHighestRow();

                // Style data rows - alternate colors
                for ($row = 2; $row <= $lastRow; $row++) {
                    $backgroundColor = ($row % 2 === 0) ? 'F9FAFB' : 'FFFFFF';

                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $backgroundColor],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'D1D5DB'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);

                    // Right align numeric columns
                    $sheet->getStyle("G{$row}:I{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Add statistics section
                $statsRow = $lastRow + 3;

                // Statistics label and data
                $statistics = $this->filterService->getStatistics($this->filters);

                $sheet->setCellValue("A{$statsRow}", 'SUMMARY STATISTICS');
                $sheet->getStyle("A{$statsRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F2937']],
                ]);

                $statsRow++;

                // Total Receipts
                $sheet->setCellValue("A{$statsRow}", 'Total Receipts:');
                $sheet->setCellValue("B{$statsRow}", $statistics['total_receipts']);
                $this->styleStat($sheet, $statsRow, '3B82F6');

                $statsRow++;

                // Total Trucks
                $sheet->setCellValue("A{$statsRow}", 'Total Trucks:');
                $sheet->setCellValue("B{$statsRow}", $statistics['total_trucks']);
                $this->styleStat($sheet, $statsRow, 'A855F7');

                $statsRow++;

                // Total Amount
                $sheet->setCellValue("A{$statsRow}", 'Total Amount (GMD):');
                $sheet->setCellValue("B{$statsRow}", $statistics['total_amount']);
                $sheet->getStyle("B{$statsRow}")->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
                $this->styleStat($sheet, $statsRow, '16A34A');

                $statsRow++;

                // Export timestamp
                $sheet->setCellValue("A{$statsRow}", 'Exported on:');
                $sheet->setCellValue("B{$statsRow}", now()->format('Y-m-d H:i:s'));
                $sheet->getStyle("A{$statsRow}:B{$statsRow}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']],
                ]);

                // Freeze the header row
                $sheet->freezePane('A2');
            },
        ];
    }

    /**
     * Style individual statistic row
     */
    private function styleStat($sheet, $row, $colorHex)
    {
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => str_replace('#', '', $colorHex) . '22'], // 22 = 10% opacity
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle("B{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * Apply styles to the sheet
     */
    public function styles($sheet)
    {
        // This is handled in registerEvents
        return [];
    }
}
