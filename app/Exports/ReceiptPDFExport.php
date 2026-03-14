<?php

namespace App\Exports;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPDFExport
{
    private Receipt $receipt;

    public function __construct(Receipt $receipt)
    {
        $this->receipt = $receipt;
    }

    public function generate()
    {
        $html = $this->generateHTML();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('margin-top', 15)
            ->setOption('margin-bottom', 15)
            ->setOption('margin-left', 15)
            ->setOption('margin-right', 15)
            ->setOption('isFontSubsettingEnabled', true);
    }

    private function generateHTML(): string
    {
        $r = $this->receipt;

        $date = $r->date?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
        $total = number_format($r->total_charge_gmd, 2);

        // Logos
        $leftLogo  = $this->base64('images/logos/left-logo.jpeg');   // Coat of Arms
        $rightLogo = $this->base64('images/logos/right-logo.jpeg');  // GRA Logo

        // Get route name - either from short route or long route
        $routeName = $r->route?->name ?? $r->longRoute?->name ?? 'N/A';
        $routeName = strtoupper($routeName);
        
        $allocation   = $r->allocationPoint?->name ?? 'Farafenni';
        $departure    = $r->allocationPoint?->name ?? $allocation;
        $destination  = $r->destination?->name ?? 'N/A';
        $billingUnit  = $r->billing_unit ?? 'N/A';
        $generatedByUser = $r->generatedByUser?->name ?? 'System';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            line-height: 1.3;
        }
        .container {
            border: 3px solid black;
            padding: 20px;
            position: relative;
            max-width: 820px;
            margin: 0 auto;
            background: white;
        }

        /* Header - Logos centered at top */
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
            display: none;
        }

        .divider {
            border-top: 2px solid black;
            margin: 8px 0;
        }

        /* Two-column layout using display: table */
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
            width: 120px;
        }

        /* Center watermark - positioned within receipt container */
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

        .goods {
            border: 2px solid black;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .goods small {
            display: block;
            margin-top: 6px;
            font-weight: normal;
            font-size: 11px;
            text-transform: none;
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
        <!-- Date aligned with logos -->
        <div style="text-align: right; font-weight: bold; font-size: 14px; margin-bottom: 8px; padding-right: 20px;">
            $date
        </div>

        <div class="logos">
            <img src="$leftLogo" alt="Coat of Arms">
            <img src="$rightLogo" alt="GRA Logo">
        </div>
        <div class="title">
            <h1>GNSW E-TRACKING RECEIPT</h1>
            <p>(Taxpayer)</p>
        </div>
    </div>

    <div class="divider"></div>

    <div class="main">
        <div class="left-col">
            <div class="row"><span class="label">Customs Office</span>: GMBJL</div>
            <div class="row"><span class="label">Declaration No</span>: {$r->sad_number}</div>
            <div class="row"><span class="label">Route</span>: $routeName</div>
            <div class="row"><span class="label">Billing Unit</span>: $billingUnit</div>
            <div class="row"><span class="label">Agent Name</span>: {$r->agent_name}</div>
            <div class="row"><span class="label">Departure</span>: $departure</div>
        </div>

        <div class="right-col">
            <div class="row"><span class="label">Receipt No</span>: {$r->receipt_number}</div>
            <div class="row"><span class="label">Receipt Date</span>: $date</div>
            <div class="row"><span class="label">Consignee Name</span>: {$r->consignee_details}</div>
            <div class="row"><span class="label">Destination</span>: $destination</div>
        </div>
    </div>

    <div class="watermark">
        <img src="$rightLogo" alt="Watermark">
    </div>

    <div class="total">
        <div>Total Charged (GMD)</div>
        <div>D $total</div>
    </div>

    <div class="goods">
        GOODS DESCRIPTION
        <small>{$r->description_of_goods}</small>
    </div>

    <div class="footer">
        <div class="signature">Signature</div>
        <div class="generated-by">Generated by: $generatedByUser</div>
    </div>

</div>

</body>
</html>
HTML;
    }

    private function base64($path)
    {
        $fullPath = public_path($path);
        if (!file_exists($fullPath)) {
            return 'https://via.placeholder.com/100?text=LOGO';
        }
        $type = pathinfo($fullPath, PATHINFO_EXTENSION);
        $data = file_get_contents($fullPath);
        return "data:image/$type;base64," . base64_encode($data);
    }

    public function download()
    {
        return $this->generate()->download("GNSW-Receipt-{$this->receipt->receipt_number}.pdf");
    }

    public function stream()
    {
        return $this->generate()->stream();
    }
}