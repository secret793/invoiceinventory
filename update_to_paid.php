<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Updating Overstay Invoice Status ===\n\n";

// Get current count
$pendingCount = DB::table('invoices')
    ->where('status', 'PP')
    ->where('overstay_days', '>', 0)
    ->count();

echo "Found {$pendingCount} pending overstay invoices\n\n";

if ($pendingCount > 0) {
    // Show sample
    $samples = DB::table('invoices')
        ->where('status', 'PP')
        ->where('overstay_days', '>', 0)
        ->limit(5)
        ->get(['reference_number', 'sad_boe', 'overstay_days', 'total_amount']);
    
    echo "Sample records:\n";
    foreach ($samples as $inv) {
        echo "- {$inv->reference_number} | {$inv->sad_boe} | Days: {$inv->overstay_days} | Amount: D {$inv->total_amount}\n";
    }
    
    // Update
    echo "\nUpdating all {$pendingCount} records to PAID status...\n";
    
    $updated = DB::table('invoices')
        ->where('status', 'PP')
        ->where('overstay_days', '>', 0)
        ->update([
            'status' => 'PD',
            'approved_by' => 1,
            'approved_at' => now(),
        ]);
    
    echo "✓ Successfully updated {$updated} invoice(s) to PAID\n\n";
    
    // Show final counts
    echo "Final Status Distribution:\n";
    $counts = DB::table('invoices')
        ->select('status', DB::raw('count(*) as count'))
        ->groupBy('status')
        ->get();
    
    foreach ($counts as $item) {
        echo "  {$item->status}: {$item->count}\n";
    }
} else {
    echo "No pending overstay invoices found.\n";
}

echo "\n=== Update Complete ===\n";
