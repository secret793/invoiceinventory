<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;

echo "=== Checking Overstay Invoice Data ===\n\n";

// Get invoices that match the Finance Officer query
$invoices = Invoice::where(function ($q) {
        $q->where('overstay_days', '>', 0)
          ->orWhere('status', 'WAIVED');
    })
    ->with(['deviceRetrieval.destination', 'deviceRetrieval.allocationPoint'])
    ->limit(5)
    ->get();

echo "Found: " . $invoices->count() . " invoices\n\n";

foreach ($invoices as $inv) {
    echo "Invoice: {$inv->reference_number}\n";
    echo "  Status: {$inv->status}\n";
    echo "  Overstay Days: {$inv->overstay_days}\n";
    echo "  Device Retrieval ID: " . ($inv->device_retrieval_id ?? 'NULL') . "\n";
    echo "  Destination (direct): " . ($inv->destination ?? 'NULL') . "\n";
    
    if ($inv->deviceRetrieval) {
        echo "  Destination (relation): " . ($inv->deviceRetrieval->destination?->name ?? 'N/A') . "\n";
        echo "  Allocation Point: " . ($inv->deviceRetrieval->allocationPoint?->name ?? 'N/A') . "\n";
    } else {
        echo "  ✗ No Device Retrieval linked\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";
