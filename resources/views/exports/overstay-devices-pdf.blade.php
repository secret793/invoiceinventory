<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Overstay Devices Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
        }

        .page-header {
            margin-bottom: 20px;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-size: 12px;
            color: #6b7280;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .meta-item {
            flex: 1;
        }

        .meta-label {
            font-weight: bold;
            color: #374151;
        }

        .meta-value {
            color: #6b7280;
        }

        .filter-section {
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #1e40af;
        }

        .filter-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .filter-list {
            columns: 2;
            gap: 15px;
        }

        .filter-item {
            break-inside: avoid;
            margin-bottom: 5px;
            font-size: 10px;
        }

        .statistics-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 15px;
        }

        .stat-card {
            background-color: #f0f9ff;
            border: 1px solid #bfdbfe;
            padding: 12px;
            border-radius: 4px;
            text-align: center;
        }

        .stat-label {
            font-size: 9px;
            font-weight: bold;
            color: #0c4a6e;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
        }

        .stat-card.amount {
            background-color: #ecfdf5;
            border-color: #a7f3d0;
        }

        .stat-card.amount .stat-label {
            color: #065f46;
        }

        .stat-card.amount .stat-value {
            color: #059669;
        }

        .stat-card.days {
            background-color: #fffbeb;
            border-color: #fde68a;
        }

        .stat-card.days .stat-label {
            color: #92400e;
        }

        .stat-card.days .stat-value {
            color: #d97706;
        }

        .stat-card.pending {
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .stat-card.pending .stat-label {
            color: #7f1d1d;
        }

        .stat-card.pending .stat-value {
            color: #dc2626;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        table thead {
            background-color: #1e40af;
            color: white;
        }

        table thead th {
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #1e3a8a;
        }

        table tbody td {
            padding: 7px 6px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
        }

        table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-danger {
            color: #dc2626;
        }

        .text-warning {
            color: #d97706;
        }

        .text-success {
            color: #059669;
        }

        .text-gray {
            color: #6b7280;
        }

        .page-break {
            page-break-after: always;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #6b7280;
            text-align: right;
        }

        .summary-section {
            margin-top: 20px;
            padding: 12px;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            page-break-inside: avoid;
        }

        .summary-title {
            font-weight: bold;
            color: #0c4a6e;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #dbeafe;
            font-size: 10px;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: bold;
            color: #0c4a6e;
        }

        .summary-value {
            color: #1e40af;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="header-title">Overstay Devices Report</div>
        <div class="header-subtitle">Device Retrieval Analysis</div>
    </div>

    <!-- Report Metadata -->
    <div class="report-meta">
        <div class="meta-item">
            <span class="meta-label">Generated:</span>
            <span class="meta-value">{{ $generatedAt->format('d/m/Y H:i:s') }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Report Period:</span>
            <span class="meta-value">
                @if($filterDescriptions)
                    Filtered
                @else
                    All Records
                @endif
            </span>
        </div>
    </div>

    <!-- Filters Applied -->
    @if($filterDescriptions)
        <div class="filter-section">
            <div class="filter-title">Applied Filters:</div>
            <div class="filter-list">
                @foreach($filterDescriptions as $description)
                    <div class="filter-item">
                        • {{ $description }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Statistics Section -->
    <div class="statistics-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Devices</div>
                <div class="stat-value">{{ number_format($totalDevices) }}</div>
            </div>

            <div class="stat-card amount">
                <div class="stat-label">Total Amount (GMD)</div>
                <div class="stat-value">D{{ number_format($totalAmount, 2) }}</div>
            </div>

            <div class="stat-card days">
                <div class="stat-label">Total Days</div>
                <div class="stat-value">{{ number_format($totalDays) }}</div>
            </div>

            <div class="stat-card days">
                <div class="stat-label">Avg Days/Device</div>
                <div class="stat-value">{{ number_format($averageDays, 1) }}</div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Pending Payment</div>
                <div class="stat-value">D{{ number_format($statistics['total_pending_payment'], 2) }}</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-label">% Pending</div>
                <div class="stat-value">
                    @php
                        $pendingPercent = $totalAmount > 0 ? round(($statistics['total_pending_payment'] / $totalAmount) * 100, 1) : 0;
                    @endphp
                    {{ $pendingPercent }}%
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Paid Amount</div>
                <div class="stat-value">D{{ number_format($totalAmount - $statistics['total_pending_payment'], 2) }}</div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">% Paid</div>
                <div class="stat-value">
                    @php
                        $paidPercent = $totalAmount > 0 ? round((($totalAmount - $statistics['total_pending_payment']) / $totalAmount) * 100, 1) : 0;
                    @endphp
                    {{ $paidPercent }}%
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th>Invoice Number</th>
                <th class="text-center">Device ID</th>
                <th class="text-center">SAD/BOE</th>
                <th>Destination</th>
                <th>Allocation Point</th>
                <th class="text-center">Days</th>
                <th class="text-right">Amount (GMD)</th>
                <th class="text-center">Status</th>
                <th class="text-center">Date</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $item)
                <tr>
                    <td class="font-bold">{{ $item[0] }}</td>
                    <td class="text-center">{{ $item[1] }}</td>
                    <td class="text-center text-gray">{{ $item[2] }}</td>
                    <td>{{ $item[3] }}</td>
                    <td>{{ $item[4] }}</td>
                    <td class="text-center">
                        @if($item[5] > 30)
                            <span class="text-danger font-bold">{{ $item[5] }}</span>
                        @elseif($item[5] > 7)
                            <span class="text-warning font-bold">{{ $item[5] }}</span>
                        @else
                            {{ $item[5] }}
                        @endif
                    </td>
                    <td class="text-right font-bold">D{{ number_format($item[6], 2) }}</td>
                    <td class="text-center">
                        @if($item[7] === 'PP')
                            <span class="text-danger font-bold">{{ $item[7] }}</span>
                        @elseif($item[7] === 'PD')
                            <span class="text-success font-bold">{{ $item[7] }}</span>
                        @else
                            <span class="text-gray">{{ $item[7] }}</span>
                        @endif
                    </td>
                    <td class="text-center text-gray">{{ $item[8] }}</td>
                    <td class="text-gray">{{ $item[9] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-gray">
                        No overstay devices found matching the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-title">Report Summary</div>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Records:</span>
                <span class="summary-value">{{ number_format($totalDevices) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Amount:</span>
                <span class="summary-value">D{{ number_format($totalAmount, 2) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Pending Payment:</span>
                <span class="summary-value">D{{ number_format($statistics['total_pending_payment'], 2) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Days:</span>
                <span class="summary-value">{{ number_format($totalDays) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Average Days:</span>
                <span class="summary-value">{{ number_format($averageDays, 1) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Report Date:</span>
                <span class="summary-value">{{ $generatedAt->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This report was automatically generated on {{ $generatedAt->format('d/m/Y \a\t H:i:s') }}</p>
    </div>
</body>
</html>
