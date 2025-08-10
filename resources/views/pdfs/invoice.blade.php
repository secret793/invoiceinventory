<!DOCTYPE html>
<html>
<head>
    <title>Invoice #{{ $invoice->reference_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .invoice-details {
            margin-bottom: 20px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invoice-total {
            text-align: right;
            margin-top: 20px;
        }
        .logo {
            max-width: 150px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <h1>Invoice Details</h1>
            <p><strong>Reference Number:</strong> {{ $invoice->reference_number }}</p>
            <p><strong>Reference Date:</strong> {{ $invoice->reference_date ? $invoice->reference_date->format('Y-m-d') : 'N/A' }}</p>
            <p><strong>Status:</strong> {{ $invoice->status }}</p>
        </div>
        @if($invoice->logo_path)
        <div>
            <img src="{{ storage_path('app/public/' . $invoice->logo_path) }}" alt="Logo" class="logo">
        </div>
        @endif
    </div>

    <div class="invoice-details">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th colspan="2">Invoice Information</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>SAD No</td>
                    <td>{{ $invoice->sad_boe ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Regime</td>
                    <td>{{ $invoice->regime ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Agent</td>
                    <td>{{ $invoice->agent ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Route</td>
                    <td>{{ $invoice->route ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Overstay Days</td>
                    <td>{{ $invoice->overstay_days ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Penalty Amount Per Day</td>
                    <td>{{ $invoice->penalty_amount ? number_format($invoice->penalty_amount, 2) : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Device Number</td>
                    <td>{{ $invoice->device_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Description</td>
                    <td>{{ $invoice->description ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Paid By</td>
                    <td>{{ $invoice->paid_by ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Received By</td>
                    <td>{{ $invoice->received_by ?? 'N/A' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="invoice-total">
        <h3>Total Amount: {{ $invoice->total_amount ? number_format($invoice->total_amount, 2) : 'N/A' }}</h3>
    </div>

    @if($deviceRetrieval)
    <div class="device-retrieval-details">
        <h2>Device Retrieval Information</h2>
        <table class="invoice-table">
            <tbody>
                <tr>
                    <td>Finance Status</td>
                    <td>{{ $deviceRetrieval->finance_status ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Finance Approved At</td>
                    <td>{{ $deviceRetrieval->finance_approved_at ? $deviceRetrieval->finance_approved_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</body>
</html>
