<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧹 Cleaning Up Duplicate ConfirmedAffixLog Records\n";
echo "================================================\n\n";

// First, let's analyze the duplicates
echo "📊 Analyzing Duplicate Records:\n";

$duplicates = DB::select("
    SELECT 
        device_id, 
        boe, 
        COUNT(*) as duplicate_count,
        GROUP_CONCAT(id ORDER BY created_at) as record_ids,
        GROUP_CONCAT(status ORDER BY created_at) as statuses,
        MIN(created_at) as first_created,
        MAX(created_at) as last_created
    FROM confirmed_affix_logs 
    GROUP BY device_id, boe 
    HAVING COUNT(*) > 1
    ORDER BY duplicate_count DESC, device_id
");

if (empty($duplicates)) {
    echo "✅ No duplicate records found!\n";
    exit(0);
}

echo "Found " . count($duplicates) . " sets of duplicate records:\n\n";

$totalDuplicates = 0;
$recordsToDelete = [];

foreach ($duplicates as $duplicate) {
    $ids = explode(',', $duplicate->record_ids);
    $statuses = explode(',', $duplicate->statuses);
    $duplicateCount = $duplicate->duplicate_count;
    $totalDuplicates += $duplicateCount - 1; // Keep one, delete the rest
    
    echo "🔍 Device ID: {$duplicate->device_id}, BOE: {$duplicate->boe}\n";
    echo "   Duplicates: {$duplicateCount} records\n";
    echo "   Record IDs: {$duplicate->record_ids}\n";
    echo "   Statuses: {$duplicate->statuses}\n";
    echo "   First Created: {$duplicate->first_created}\n";
    echo "   Last Created: {$duplicate->last_created}\n";
    
    // Keep the first record (original), mark others for deletion
    $keepId = $ids[0];
    $deleteIds = array_slice($ids, 1);
    
    echo "   ✅ Keeping: ID {$keepId} (first record)\n";
    echo "   ❌ Deleting: IDs " . implode(', ', $deleteIds) . "\n\n";
    
    $recordsToDelete = array_merge($recordsToDelete, $deleteIds);
}

echo "📋 Summary:\n";
echo "   Total duplicate sets: " . count($duplicates) . "\n";
echo "   Total records to delete: {$totalDuplicates}\n";
echo "   Records to keep: " . count($duplicates) . " (one per device/BOE)\n\n";

// Ask for confirmation
echo "⚠️  WARNING: This will permanently delete {$totalDuplicates} duplicate records!\n";
echo "Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

// Perform the cleanup
echo "\n🗑️  Deleting duplicate records...\n";

try {
    DB::beginTransaction();
    
    // Delete duplicates in batches
    $batchSize = 100;
    $batches = array_chunk($recordsToDelete, $batchSize);
    $deletedCount = 0;
    
    foreach ($batches as $batch) {
        $deleted = DB::table('confirmed_affix_logs')
            ->whereIn('id', $batch)
            ->delete();
        
        $deletedCount += $deleted;
        echo "   Deleted batch: {$deleted} records\n";
    }
    
    DB::commit();
    
    echo "\n✅ Cleanup completed successfully!\n";
    echo "   Total records deleted: {$deletedCount}\n";
    
    // Verify cleanup
    echo "\n🔍 Verifying cleanup...\n";
    $remainingDuplicates = DB::select("
        SELECT COUNT(*) as count
        FROM (
            SELECT device_id, boe, COUNT(*) as duplicate_count
            FROM confirmed_affix_logs 
            GROUP BY device_id, boe 
            HAVING COUNT(*) > 1
        ) as duplicates
    ");
    
    $remainingCount = $remainingDuplicates[0]->count ?? 0;
    
    if ($remainingCount == 0) {
        echo "✅ All duplicates successfully removed!\n";
    } else {
        echo "⚠️  Warning: {$remainingCount} duplicate sets still remain.\n";
    }
    
    // Show final statistics
    $totalRecords = DB::table('confirmed_affix_logs')->count();
    echo "\n📊 Final Statistics:\n";
    echo "   Total ConfirmedAffixLog records: {$totalRecords}\n";
    echo "   Duplicate sets remaining: {$remainingCount}\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
    echo "   Transaction rolled back. No records were deleted.\n";
}

echo "\n✅ Cleanup script completed!\n";
