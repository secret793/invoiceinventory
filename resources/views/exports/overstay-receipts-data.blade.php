<table>
    <thead>
        <tr>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Receipt #</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">SAD Number</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Device ID</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Destination</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Allocation Point</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Route</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Overstay Days</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Overstay Amount (D)</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Status</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Regime</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Agent</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Driver Name</th>
            <th style="background-color: #4B5563; color: white; font-weight: bold; border: 1px solid #000; padding: 8px;">Created Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->reference_number }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->sad_boe ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->deviceRetrieval?->device?->device_id ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->deviceRetrieval?->destination?->name ?? $invoice->destination ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->deviceRetrieval?->allocationPoint?->name ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->route ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">{{ $invoice->overstay_days ?? 0 }}</td>
                <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">{{ number_format($invoice->penalty_amount ?? 0, 2) }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->status }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->regime ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->agent ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->driver_name ?? '-' }}</td>
                <td style="border: 1px solid #ddd; padding: 6px;">{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="13" style="border: 1px solid #ddd; padding: 6px; text-align: center;">No invoices found</td>
            </tr>
        @endforelse
    </tbody>
</table>
