<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class OverstayReceiptsExport implements WithMultipleSheets
{
    protected $invoices;
    protected $statistics;

    public function __construct($invoices, $statistics)
    {
        $this->invoices = $invoices;
        $this->statistics = $statistics;
    }

    /**
     * Return an array of sheets
     */
    public function sheets(): array
    {
        return [
            new OverstayReceiptsDataSheet($this->invoices),
            new OverstayReceiptsSummarySheet($this->statistics),
        ];
    }
}

/**
 * Main data sheet with all invoices
 */
class OverstayReceiptsDataSheet implements FromView, WithTitle
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function view(): View
    {
        return view('exports.overstay-receipts-data', [
            'invoices' => $this->invoices,
        ]);
    }

    public function title(): string
    {
        return 'Overstay Receipts';
    }
}

/**
 * Summary sheet with statistics and filters
 */
class OverstayReceiptsSummarySheet implements FromView, WithTitle
{
    protected $statistics;

    public function __construct($statistics)
    {
        $this->statistics = $statistics;
    }

    public function view(): View
    {
        return view('exports.overstay-receipts-summary', [
            'statistics' => $this->statistics,
        ]);
    }

    public function title(): string
    {
        return 'Summary';
    }
}
