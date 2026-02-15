<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use App\Models\Destination;
use Illuminate\Support\Facades\DB;

echo "=== Testing Overstay Invoice Destination Display & Filter ===\n\n";

// Test 1: Check destination display
echo "TEST 1: Destination Display\n";
echo "----------------------------\n";
$invoices = Invoice::where(function ($q) {
        $q->where('overstay_days', '>', 0)->orWhere('status', 'WAIVED');
    })
    ->with(['deviceRetrieval.destination'])
    ->limit(5)
    ->get();

echo "Found {$invoices->count()} invoices\n\n";
foreach ($invoices as $inv) {
    $destName = $inv->deviceRetrieval?->destination?->name ?? $inv->destination ?? '-';
    echo "  {$inv->reference_number} | Destination: {$destName}\n";
}

// Test 2: Check available destinations for filter
echo "\n\nTEST 2: Available Destinations for Filter\n";
echo "-------------------------------------------\n";
$availableDestinations = Destination::query()
    ->whereHas('deviceRetrievals', function ($q) {
        $q->whereHas('invoices', function ($innerQ) {
            $innerQ->where(function ($cq) {
                $cq->where('overstay_days', '>', 0)
                  ->orWhere('status', 'WAIVED');
            });
        });
    })
    ->orderBy('name')
    ->pluck('name', 'id');

echo "Total destinations with overstay invoices: {$availableDestinations->count()}\n\n";
foreach ($availableDestinations as $id => $name) {
    $count = DB::table('invoices')
        ->join('device_retrievals', 'invoices.device_retrieval_id', '=', 'device_retrievals.id')
        ->where('device_retrievals.destination_id', $id)
        ->where(function ($q) {
            $q->where('invoices.overstay_days', '>', 0)
              ->orWhere('invoices.status', 'WAIVED');
        })
        ->count();
    
    echo "  [{$id}] {$name} ({$count} invoices)\n";
}

// Test 3: Test destination filter
echo "\n\nTEST 3: Test Destination Filter\n";
echo "---------------------------------\n";
if ($availableDestinations->count() > 0) {
    $testDestId = $availableDestinations->keys()->first();
    $testDestName = $availableDestinations->first();
    
    echo "Testing filter for: {$testDestName} (ID: {$testDestId})\n";
    
    $filtered = Invoice::where(function ($q) {
            $q->where('overstay_days', '>', 0)->orWhere('status', 'WAIVED');
        })
        ->whereHas('deviceRetrieval', function ($q) use ($testDestId) {
            $q->where('destination_id', $testDestId);
        })
        ->with(['deviceRetrieval.destination'])
        ->limit(3)
        ->get();
    
    echo "Found {$filtered->count()} invoices for this destination:\n";
    foreach ($filtered as $inv) {
        echo "  - {$inv->reference_number} | {$inv->deviceRetrieval->destination->name}\n";
    }
} else {
    echo "No destinations found with overstay invoices\n";
}

echo "\n\n✓ All tests completed successfully!\n";
echo "===================================\n";
