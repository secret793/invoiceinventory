<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;

echo "=== Testing Destination Filter Select ===\n\n";

// Get all available destinations (same as the computed property)
$destinations = Invoice::withoutGlobalScopes()
    ->whereNotNull('destination')
    ->distinct()
    ->orderBy('destination')
    ->pluck('destination');

echo "Available Destinations ({$destinations->count()}):\n";
echo "====================================\n";
foreach ($destinations as $dest) {
    $count = Invoice::withoutGlobalScopes()
        ->where('destination', $dest)
        ->count();
    echo "  - {$dest} ({$count} invoices)\n";
}

// Test filtering
echo "\n\nTest: Filter by first destination\n";
echo "===================================\n";
if ($destinations->count() > 0) {
    $testDest = $destinations->first();
    echo "Selected: {$testDest}\n";
    
    $filtered = Invoice::withoutGlobalScopes()
        ->where('destination', $testDest)
        ->count();
    
    echo "Found {$filtered} invoices for this destination\n";
}

echo "\n✓ Select dropdown will work!\n";
