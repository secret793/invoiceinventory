<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->reference_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-header h1 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .invoice-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .invoice-details th {
            text-align: left;
            padding: 10px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        .invoice-details td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-total {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            margin-top: 30px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>INVOICE</h1>
        <p>Reference: {{ $invoice->reference_number }}</p>
        <p>Date: {{ $invoice->reference_date->format('d/m/Y') }}</p>
    </div>

    <table class="invoice-details">
        <tr>
            <th>Device Number:</th>
            <td>{{ $invoice->device_number }}</td>
            <th>SAD/BOE Number:</th>
            <td>{{ $invoice->sad_boe }}</td>
        </tr>
        <tr>
            <th>Agent:</th>
            <td>{{ $invoice->agent }}</td>
            <th>Regime:</th>
            <td>{{ $invoice->regime }}</td>
        </tr>
        <tr>
            <th>Route:</th>
            <td>{{ $invoice->route }}</td>
            <th>Overstay Days:</th>
            <td>{{ $invoice->overstay_days }}</td>
        </tr>
        <tr>
            <th>Penalty Amount:</th>
            <td>{{ number_format($invoice->penalty_amount, 2) }}</td>
            <th>Total Amount:</th>
            <td>{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <th>Paid By:</th>
            <td>{{ $invoice->paid_by }}</td>
            <th>Received By:</th>
            <td>{{ $invoice->received_by }}</td>
        </tr>
    </table>

    <div class="invoice-total">
        Total Amount: {{ number_format($invoice->total_amount, 2) }}
    </div>

    <div class="footer">
        <p>Thank you for your business! If you have any questions, please don't hesitate to contact us.</p>
    </div>
</body>
</html>
