<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OverstayReceiptExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private $records, private $statistics) {}

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Reference Number',
            'SAD/BOE',
            'Device ID',
            'Regime',
            'Agent',
            'Route',
            'Overstay Days',
            'Penalty Per Day (D)',
            'Total Amount (D)',
            'Status',
            'Created Date',
        ];
    }

    public function map($record): array
    {
        return [
            $record->reference_number,
            $record->sad_boe,
            $record->device_number,
            $record->regime,
            $record->agent,
            $record->route,
            $record->overstay_days,
            $record->penalty_amount,
            $record->total_amount,
            $record->status,
            $record->reference_date?->format('Y-m-d'),
        ];
    }
}
