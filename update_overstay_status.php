<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

echo "=== Overstay Invoice Status Update ===\n\n";

// Get current status counts
echo "Current Status Distribution:\n";
echo "----------------------------\n";
$statusCounts = DB::table('invoices')
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

foreach ($statusCounts as $item) {
    echo "{$item->status}: {$item->count}\n";
}

// Get pending overstay invoices (with overstay_days > 0)
echo "\n\nPending Overstay Receipts:\n";
echo "-----------------------------------\n";
$pendingOverstay = Invoice::where('status', 'PP')
    ->where('overstay_days', '>', 0)
    ->get();

echo "Found: " . $pendingOverstay->count() . " pending overstay receipts\n\n";

if ($pendingOverstay->count() > 0) {
    echo "Sample records:\n";
    foreach ($pendingOverstay->take(5) as $invoice) {
        echo "- {$invoice->reference_number} | {$invoice->sad_boe} | Days: {$invoice->overstay_days} | Amount: D {$invoice->total_amount} | Created: {$invoice->created_at}\n";
    }
    
    echo "\n\nDo you want to update all pending overstay receipts to PAID status? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) === 'yes' || strtolower($line) === 'y') {
        echo "\nUpdating records...\n";
        
        $updated = Invoice::where('status', 'PP')
            ->where('overstay_days', '>', 0)
            ->update([
                'status' => 'PD',
                'approved_by' => 1, // System/Admin user
                'approved_at' => now(),
            ]);
        
        echo "✓ Updated {$updated} invoice(s) to PAID status\n";
        
        // Show updated counts
        echo "\n\nUpdated Status Distribution:\n";
        echo "----------------------------\n";
        $updatedCounts = DB::table('invoices')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        
        foreach ($updatedCounts as $item) {
            echo "{$item->status}: {$item->count}\n";
        }
        
        echo "\n✓ All overstay receipts have been marked as PAID!\n";
    } else {
        echo "Update cancelled.\n";
    }
} else {
    echo "No pending overstay receipts to update.\n";
}

echo "\n=== Update Complete ===\n";
