<?php

namespace App\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class GeneratedReceiptsPDFExport
{
    protected Collection $receipts;
    protected ?array $statistics;
    protected array $filters;

    public function __construct(Collection $receipts, ?array $statistics = null, array $filters = [])
    {
        $this->receipts   = $receipts;
        $this->statistics = $statistics;
        $this->filters    = $filters;
    }

    public function generate()
    {
        $html = $this->buildHtml();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('margin-top', 12)
            ->setOption('margin-bottom', 12)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);
    }

    private function buildHtml(): string
    {
        $rows = '';
        foreach ($this->receipts as $i => $r) {
            $bg   = $i % 2 === 0 ? '#f9f9f9' : '#ffffff';
            $date = $r->created_at?->format('d/m/Y H:i') ?? 'N/A';

            $rows .= sprintf(
                '<tr style="background:%s">
                    <td>%d</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td style="text-align:right">%s</td>
                    <td>%s</td>
                </tr>',
                $bg,
                $i + 1,
                htmlspecialchars($r->receipt_number ?? ''),
                htmlspecialchars($r->sad_number ?? 'N/A'),
                htmlspecialchars($r->route?->name ?? ($r->longRoute?->name ?? 'N/A')),
                htmlspecialchars($r->allocationPoint?->name ?? 'N/A'),
                htmlspecialchars($r->destination?->name ?? 'N/A'),
                $date,
                (int) ($r->moving_trucks ?? 0),
                number_format((float) ($r->total_charge_gmd ?? 0), 2),
                htmlspecialchars($r->generatedByUser?->name ?? 'System')
            );
        }

        $statsHtml = '';
        if ($this->statistics) {
            $statsHtml = sprintf(
                '<table style="width:100%%;border-collapse:collapse;margin-bottom:14px;font-size:11px;">
                    <tr>
                        <td style="width:20%%;padding:6px 10px;background:#1e40af;color:#fff;font-weight:bold;border-radius:4px 0 0 4px;">Total Receipts: %d</td>
                        <td style="width:20%%;padding:6px 10px;background:#1d4ed8;color:#fff;font-weight:bold;">Total Trucks: %d</td>
                        <td style="width:20%%;padding:6px 10px;background:#2563eb;color:#fff;font-weight:bold;">Total Charged (GMD): %s</td>
                        <td style="width:20%%;padding:6px 10px;background:#3b82f6;color:#fff;font-weight:bold;">Short Routes: %d</td>
                        <td style="width:20%%;padding:6px 10px;background:#60a5fa;color:#fff;font-weight:bold;border-radius:0 4px 4px 0;">Long Routes: %d</td>
                    </tr>
                </table>',
                $this->statistics['total_receipts'] ?? 0,
                $this->statistics['total_trucks'] ?? 0,
                number_format((float) ($this->statistics['total_amount'] ?? 0), 2),
                $this->statistics['total_short_routes'] ?? 0,
                $this->statistics['total_long_routes'] ?? 0
            );
        }

        $generatedAt = now()->format('d/m/Y H:i');
        $total       = $this->receipts->count();

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; margin: 0; padding: 0; }
    h1   { font-size: 16px; color: #1e40af; margin: 0 0 2px 0; }
    .sub { font-size: 10px; color: #6b7280; margin-bottom: 12px; }
    table.main { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    table.main th {
        background: #1e40af; color: #fff; padding: 6px 5px;
        text-align: left; border: 1px solid #1e40af;
    }
    table.main td { padding: 5px; border: 1px solid #e5e7eb; vertical-align: top; }
    .footer { margin-top: 12px; font-size: 9px; color: #9ca3af; text-align: right; }
</style>
</head>
<body>
    <h1>Generated Receipts Report</h1>
    <div class="sub">Generated: {$generatedAt} &nbsp;&bull;&nbsp; Total records: {$total}</div>

    {$statsHtml}

    <table class="main">
        <thead>
            <tr>
                <th>#</th>
                <th>Receipt No.</th>
                <th>SAD/T1</th>
                <th>Route</th>
                <th>Allocation Point</th>
                <th>Destination</th>
                <th>Date &amp; Time</th>
                <th>Trucks</th>
                <th style="text-align:right">Total (GMD)</th>
                <th>Generated By</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>

    <div class="footer">eTracking Inventory &bull; Confidential</div>
</body>
</html>
HTML;
    }
}
