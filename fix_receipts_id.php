#!/usr/bin/env php
<?php

/**
 * Fix Receipts Table ID Column - Auto Increment Issue
 * 
 * This script fixes the 'id' column in the receipts table to have AUTO_INCREMENT
 * 
 * Usage: php fix_receipts_id.php
 * Or from Laravel root: php artisan tinker < fix_receipts_id.php
 */

echo "=================================================================\n";
echo "FIX RECEIPTS TABLE ID - AUTO INCREMENT\n";
echo "=================================================================\n\n";

// Check current table structure
echo "Step 1: Checking current receipts table structure...\n";
try {
    $currentStructure = DB::select("SHOW CREATE TABLE receipts");
    echo "Current CREATE TABLE statement:\n";
    echo $currentStructure[0]->{'Create Table'} . "\n\n";
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "\n";
}

// Check if id has AUTO_INCREMENT
echo "Step 2: Checking if 'id' column has AUTO_INCREMENT...\n";
try {
    $columns = DB::select("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
    $idColumn = $columns[0];
    
    echo "Current 'id' column definition:\n";
    echo "  Type: " . $idColumn->Type . "\n";
    echo "  Null: " . $idColumn->Null . "\n";
    echo "  Key: " . $idColumn->Key . "\n";
    echo "  Default: " . ($idColumn->Default ?? 'NULL') . "\n";
    echo "  Extra: " . $idColumn->Extra . "\n\n";
    
    if (strpos($idColumn->Extra, 'auto_increment') === false) {
        echo "⚠️  WARNING: 'id' column does NOT have AUTO_INCREMENT!\n\n";
        
        echo "Step 3: Applying fix...\n";
        
        // Apply the fix
        DB::statement("ALTER TABLE `receipts` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        
        echo "✅ Successfully modified 'id' column to have AUTO_INCREMENT\n\n";
        
        // Verify the fix
        echo "Step 4: Verifying the fix...\n";
        $verifyColumns = DB::select("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
        $verifiedColumn = $verifyColumns[0];
        
        echo "Updated 'id' column definition:\n";
        echo "  Type: " . $verifiedColumn->Type . "\n";
        echo "  Extra: " . $verifiedColumn->Extra . "\n";
        
        if (strpos($verifiedColumn->Extra, 'auto_increment') !== false) {
            echo "\n✅ SUCCESS! The 'id' column now has AUTO_INCREMENT\n";
        } else {
            echo "\n❌ ERROR: Fix did not apply correctly\n";
        }
        
    } else {
        echo "✅ Good! The 'id' column already has AUTO_INCREMENT\n";
        echo "No fix needed.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTrying alternative approach...\n";
    
    try {
        // Alternative approach - drop and recreate
        DB::statement("ALTER TABLE `receipts` DROP PRIMARY KEY");
        DB::statement("ALTER TABLE `receipts` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        DB::statement("ALTER TABLE `receipts` ADD PRIMARY KEY (`id`)");
        
        echo "✅ Alternative fix applied successfully!\n";
    } catch (Exception $e2) {
        echo "❌ Alternative approach also failed: " . $e2->getMessage() . "\n";
    }
}

echo "\n=================================================================\n";
echo "Script completed.\n";
echo "=================================================================\n";
