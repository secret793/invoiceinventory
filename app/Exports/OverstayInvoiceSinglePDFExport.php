<?php

namespace App\Exports;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class OverstayInvoiceSinglePDFExport
{
    /**
     * @var Invoice
     */
    private Invoice $invoice;

    /**
     * Constructor
     * 
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Generate HTML content
     * 
     * @param Invoice $invoice
     * @return string
     */
    private function generateHTML(Invoice $invoice): string
    {
        $leftLogoPath = public_path('images/logos/left-logo.jpeg');
        $rightLogoPath = public_path('images/logos/right-logo.jpeg');

        $leftLogoBase64 = '';
        $rightLogoBase64 = '';

        if (file_exists($leftLogoPath)) {
            $leftLogoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($leftLogoPath));
        }

        if (file_exists($rightLogoPath)) {
            $rightLogoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($rightLogoPath));
        }

        $generatedAt = now();
        $statusLabel = match($invoice->status) {
            'PP' => 'PENDING PAYMENT',
            'PD' => 'PAID',
            'WAIVED' => 'PAYMENT WAIVED',
            default => 'UNKNOWN'
        };

        $statusColor = match($invoice->status) {
            'PP' => 'color: #991b1b;',
            'PD' => 'color: #065f46;',
            'WAIVED' => 'color: #0c4a6e;',
            default => 'color: #1f2937;'
        };

        $deviceId = $invoice->deviceRetrieval?->device?->device_id ?? 'N/A';
        $destination = $invoice->deviceRetrieval?->destination?->name ?? 'N/A';
        $allocationPoint = $invoice->deviceRetrieval?->allocationPoint?->name ?? 'N/A';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Overstay Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.3;
            padding: 10px;
        }

        .container {
            border: 3px solid black;
            padding: 20px;
            position: relative;
            max-width: 820px;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            position: relative;
        }

        .logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            margin-bottom: 6px;
        }

        .logos img {
            height: 65px;
            width: auto;
            object-fit: contain;
        }

        .title h1 {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
        }

        .title p {
            margin: 2px 0 0;
            font-size: 11px;
        }

        .datetime {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
            padding-right: 20px;
        }

        .divider {
            border-top: 2px solid black;
            margin: 8px 0;
        }

        .main {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .left-col, .right-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }

        .left-col {
            border-right: 1px solid black;
        }

        .row {
            margin-bottom: 8px;
            font-size: 12px;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 140px;
        }

        .watermark {
            position: absolute;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.15;
            pointer-events: none;
            z-index: 1;
        }

        .watermark img {
            height: 300px;
        }

        .total {
            border-top: 3px double black;
            border-bottom: 3px double black;
            padding: 10px 0;
            margin: 12px 0;
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: bold;
        }

        .status-box {
            border: 2px solid black;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .status-box small {
            display: block;
            margin-top: 6px;
            font-weight: normal;
            font-size: 11px;
        }

        .footer {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: flex-end;
        }

        .signature {
            width: 300px;
            border-bottom: 1px solid black;
            text-align: center;
            padding-top: 4px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .generated-by {
            margin-top: 2px;
            font-size: 10px;
            color: #555;
            font-style: italic;
            text-align: right;
            width: 300px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="datetime">{$generatedAt->format('d/m/Y H:i')}</div>
        
        <div class="logos">
            {$this->getLogo($leftLogoBase64, 'Coat of Arms')}
            {$this->getLogo($rightLogoBase64, 'GRA Logo')}
        </div>

        <div class="title">
            <h1>OVERSTAY RECEIPT</h1>
            <p>(Device Retrieval System)</p>
        </div>
    </div>

    <div class="divider"></div>

    <div class="main">
        <div class="left-col">
            <div class="row"><span class="label">Invoice No</span>: {$invoice->reference_number}</div>
            <div class="row"><span class="label">Device ID</span>: {$deviceId}</div>
            <div class="row"><span class="label">SAD/BOE</span>: {$this->getFieldValue($invoice->sad_boe)}</div>
            <div class="row"><span class="label">Overstay Days</span>: {$invoice->overstay_days}</div>
            <div class="row"><span class="label">Invoice Date</span>: {$this->getDateValue($invoice->reference_date)}</div>
        </div>

        <div class="right-col">
            <div class="row"><span class="label">Destination</span>: {$destination}</div>
            <div class="row"><span class="label">Allocation Point</span>: {$allocationPoint}</div>
            <div class="row"><span class="label">Status</span>: <span style="color: #065f46;">PAID</span></div>
            <div class="row"><span class="label">Regime</span>: {$this->getFieldValue($invoice->regime)}</div>
        </div>
    </div>

    {$this->getWatermark($rightLogoBase64)}

    <div class="total">
        <div>Total Amount (GMD)</div>
        <div>D {number_format($invoice->total_amount, 2)}</div>
    </div>

    <div class="status-box">
        PAID
        <small>{$this->getFieldValue($invoice->description, 'Overstay Charge')}</small>
    </div>

    <div class="footer">
        <div class="signature">Signature</div>
        <div class="generated-by">Generated: {$generatedAt->format('d/m/Y H:i')}</div>
    </div>
</div>

</body>
</html>
HTML;

        return $html;
    }

    /**
     * Get logo HTML
     */
    private function getLogo(string $base64, string $alt): string
    {
        if (empty($base64)) {
            return '';
        }
        return "<img src=\"{$base64}\" alt=\"{$alt}\">";
    }

    /**
     * Get watermark HTML
     */
    private function getWatermark(string $base64): string
    {
        if (empty($base64)) {
            return '';
        }
        return "<div class=\"watermark\"><img src=\"{$base64}\" alt=\"Watermark\"></div>";
    }

    /**
     * Get field value or default
     */
    private function getFieldValue(?string $value, string $default = 'N/A'): string
    {
        return !empty($value) ? $value : $default;
    }

    /**
     * Get date value formatted
     */
    private function getDateValue($date): string
    {
        return $date ? $date->format('d/m/Y H:i') : 'N/A';
    }

    /**
     * Get status label
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'PP' => 'PENDING PAYMENT',
            'PD' => 'PAID',
            'WAIVED' => 'PAYMENT WAIVED',
            default => 'UNKNOWN'
        };
    }

    /**
     * Generate PDF
     * 
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate()
    {
        // Load invoice with relationships
        $invoice = Invoice::with([
            'deviceRetrieval.device',
            'deviceRetrieval.destination',
            'deviceRetrieval.allocationPoint',
            'approver',
            'waivedByUser'
        ])->findOrFail($this->invoice->id);

        // Generate HTML
        $html = $this->generateHTML($invoice);

        // Generate PDF
        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isPhpEnabled' => true,
                'isRemoteEnabled' => true,
                'logOutputFile' => storage_path('logs/pdf.log'),
            ]);
    }

    /**
     * Download PDF
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        $filename = 'invoice_' . $this->invoice->reference_number . '.pdf';
        return $this->generate()->download($filename);
    }

    /**
     * Stream PDF to browser
     * 
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream()
    {
        return $this->generate()->stream();
    }
}
