<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n========================================\n";
echo "  Receipts Table Diagnostics\n";
echo "========================================\n\n";

try {
    // Show full table structure
    echo "1. Full table structure:\n";
    $createTable = DB::select("SHOW CREATE TABLE receipts");
    echo $createTable[0]->{'Create Table'} . "\n\n";
    
    // Count receipts
    echo "2. Current receipt count:\n";
    $count = DB::table('receipts')->count();
    echo "Total receipts: " . $count . "\n\n";
    
    // Check for last receipt
    if ($count > 0) {
        echo "3. Last receipt:\n";
        $last = DB::table('receipts')->orderBy('id', 'desc')->first();
        echo "ID: " . $last->id . "\n";
        echo "Receipt Number: " . $last->receipt_number . "\n\n";
    }
    
    echo "✓ Table is working correctly!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n========================================\n\n";
