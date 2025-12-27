<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Overstay Invoice</title>
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
            width: 140px;
        }

        /* Center watermark - positioned within invoice container */
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

        .status-pp {
            color: #991b1b;
            font-weight: bold;
        }

        .status-pd {
            color: #065f46;
            font-weight: bold;
        }

        .status-waived {
            color: #0c4a6e;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <!-- Date aligned with logos -->
        <div class="datetime">{{ $generatedAt->format('d/m/Y H:i') }}</div>

        <div class="logos">
            @php
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
            @endphp
            
            @if($leftLogoBase64)
                <img src="{{ $leftLogoBase64 }}" alt="Coat of Arms">
            @endif
            @if($rightLogoBase64)
                <img src="{{ $rightLogoBase64 }}" alt="GRA Logo">
            @endif
        </div>

        <div class="title">
            <h1>OVERSTAY INVOICE</h1>
            <p>(Device Retrieval System)</p>
        </div>
    </div>

    <div class="divider"></div>

    <div class="main">
        <div class="left-col">
            <div class="row"><span class="label">Invoice No</span>: {{ $invoice->reference_number }}</div>
            <div class="row"><span class="label">Device ID</span>: {{ $invoice->deviceRetrieval?->device?->device_id ?? 'N/A' }}</div>
            <div class="row"><span class="label">SAD/BOE</span>: {{ $invoice->sad_boe ?? 'N/A' }}</div>
            <div class="row"><span class="label">Overstay Days</span>: {{ $invoice->overstay_days }}</div>
            <div class="row"><span class="label">Invoice Date</span>: {{ $invoice->reference_date ? $invoice->reference_date->format('d/m/Y H:i') : 'N/A' }}</div>
        </div>

        <div class="right-col">
            <div class="row"><span class="label">Destination</span>: {{ $invoice->deviceRetrieval?->destination?->name ?? 'N/A' }}</div>
            <div class="row"><span class="label">Allocation Point</span>: {{ $invoice->deviceRetrieval?->allocationPoint?->name ?? 'N/A' }}</div>
            <div class="row">
                <span class="label">Status</span>: 
                @if($invoice->status === 'PP')
                    <span class="status-pp">PENDING PAYMENT</span>
                @elseif($invoice->status === 'PD')
                    <span class="status-pd">PAID</span>
                @else
                    <span class="status-waived">WAIVED</span>
                @endif
            </div>
            <div class="row"><span class="label">Regime</span>: {{ $invoice->regime ?? 'N/A' }}</div>
        </div>
    </div>

    @if($rightLogoBase64)
        <div class="watermark">
            <img src="{{ $rightLogoBase64 }}" alt="Watermark">
        </div>
    @endif

    <div class="total">
        <div>Total Amount (GMD)</div>
        <div>D {{ number_format($invoice->total_amount, 2) }}</div>
    </div>

    <div class="status-box">
        @if($invoice->status === 'PP')
            PENDING PAYMENT
        @elseif($invoice->status === 'PD')
            PAYMENT RECEIVED
        @else
            PAYMENT WAIVED
        @endif
        <small>{{ $invoice->description ?? 'Overstay Charge' }}</small>
    </div>

    <div class="footer">
        <div class="signature">Signature</div>
        <div class="generated-by">Generated: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

</div>

</body>
</html>
