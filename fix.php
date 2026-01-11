<?php
// Simple Receipts ID Fix - Works with Laravel
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\nFixing receipts table ID column...\n";

try {
    // Check current structure
    $columns = DB::select("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
    if (!empty($columns)) {
        $column = $columns[0];
        echo "Current status: " . ($column->Extra ?? 'none') . "\n";
        
        if (stripos($column->Extra ?? '', 'auto_increment') === false) {
            echo "Applying AUTO_INCREMENT fix...\n";
            
            // Drop primary key first, then recreate with AUTO_INCREMENT
            DB::statement("ALTER TABLE `receipts` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
            
            echo "✓ SUCCESS! ID column now has AUTO_INCREMENT.\n";
        } else {
            echo "✓ ID column already has AUTO_INCREMENT!\n";
        }
    }
    
    echo "✓ You can now generate receipts without errors.\n\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n\n";
    
    // Try alternative approach
    echo "Trying alternative fix...\n";
    try {
        DB::statement("ALTER TABLE `receipts` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        echo "✓ SUCCESS with alternative method!\n\n";
    } catch (Exception $e2) {
        echo "✗ Alternative failed: " . $e2->getMessage() . "\n\n";
    }
}
