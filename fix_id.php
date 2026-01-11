<?php
/**
 * Simple Fix for Receipts Table ID Column
 * Run: php artisan tinker
 * Then paste this entire script
 * Or run: php fix_id.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "  Fix Receipts Table ID Column\n";
echo "========================================\n\n";

try {
    // Check current column
    echo "Checking receipts.id column...\n";
    $columns = DB::select("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
    
    if (empty($columns)) {
        die("ERROR: receipts table or id column not found!\n");
    }
    
    $column = $columns[0];
    echo "Current Extra: " . ($column->Extra ?? 'none') . "\n\n";
    
    if (stripos($column->Extra ?? '', 'auto_increment') === false) {
        echo "⚠  Missing AUTO_INCREMENT! Fixing...\n\n";
        
        DB::statement("ALTER TABLE `receipts` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
        
        echo "✓ Fixed successfully!\n\n";
        
        // Verify
        $columns = DB::select("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
        echo "Updated Extra: " . ($columns[0]->Extra ?? 'none') . "\n\n";
        
        echo "✓✓ You can now generate receipts!\n";
    } else {
        echo "✓ Already has AUTO_INCREMENT. No fix needed!\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n\n";
