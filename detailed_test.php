<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING AFFIXED_BY COLUMN FUNCTIONALITY ===\n\n";

// Check if the column exists in the database
try {
    $columns = collect(DB::select("DESCRIBE confirmed_affix_logs"));
    $affixedByColumn = $columns->firstWhere('Field', 'affixed_by');

    echo "📋 Database Schema Check:\n";
    if ($affixedByColumn) {
        echo "✅ affixed_by column exists\n";
        echo "   Type: {$affixedByColumn->Type}\n";
        echo "   Null: {$affixedByColumn->Null}\n";
        echo "   Key: {$affixedByColumn->Key}\n";
        echo "   Default: {$affixedByColumn->Default}\n";
        echo "   Extra: {$affixedByColumn->Extra}\n\n";
    } else {
        echo "❌ affixed_by column does NOT exist!\n";
        echo "Available columns:\n";
        foreach ($columns as $column) {
            echo "   - {$column->Field}\n";
        }
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check recent records to see if affixed_by is being populated
try {
    echo "📊 Recent ConfirmedAffixLog Records (last 5):\n";
    $recentRecords = \App\Models\ConfirmedAffixLog::orderBy('created_at', 'desc')
        ->take(5)
        ->get(['id', 'device_id', 'affixed_by', 'created_at']);

    if ($recentRecords->isEmpty()) {
        echo "   No records found.\n\n";
    } else {
        foreach ($recentRecords as $record) {
            $affixedByValue = $record->affixed_by ?? 'NULL';
            echo "   ID: {$record->id}, Device: {$record->device_id}, Affixed By: {$affixedByValue}, Created: {$record->created_at}\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ Error checking recent records: " . $e->getMessage() . "\n";
}

// Check if any records have affixed_by populated
try {
    $recordsWithAffixedBy = \App\Models\ConfirmedAffixLog::whereNotNull('affixed_by')->count();
    $totalRecords = \App\Models\ConfirmedAffixLog::count();

    echo "📈 Statistics:\n";
    echo "   Total ConfirmedAffixLog records: {$totalRecords}\n";
    echo "   Records with affixed_by populated: {$recordsWithAffixedBy}\n";
    echo "   Records with NULL affixed_by: " . ($totalRecords - $recordsWithAffixedBy) . "\n\n";

} catch (Exception $e) {
    echo "❌ Error checking statistics: " . $e->getMessage() . "\n";
}

// Test creating a new record
try {
    echo "🧪 Testing Record Creation:\n";

    // Find a user to test with
    $testUser = \App\Models\User::first();
    if (!$testUser) {
        echo "❌ No users found in database!\n";
        exit(1);
    }

    echo "   Using test user: {$testUser->name} (ID: {$testUser->id})\n";

    // Create test record
    $testRecord = \App\Models\ConfirmedAffixLog::create([
        'device_id' => 999999,
        'boe' => 'TEST-BOE-' . time(),
        'vehicle_number' => 'TEST-' . time(),
        'regime' => 'TEST',
        'destination' => 'TEST DESTINATION',
        'agency' => 'TEST AGENCY',
        'affixing_date' => now(),
        'status' => 'AFFIXED',
        'affixed_by' => $testUser->id,
    ]);

    echo "   ✅ Test record created (ID: {$testRecord->id})\n";
    echo "   ✅ affixed_by saved as: {$testRecord->affixed_by}\n";

    // Test relationship
    $affixedByUser = $testRecord->affixedBy;
    if ($affixedByUser) {
        echo "   ✅ Relationship working: {$affixedByUser->name}\n";
    } else {
        echo "   ❌ Relationship failed\n";
    }

    // Clean up
    $testRecord->delete();
    echo "   🧹 Test record cleaned up\n\n";

} catch (Exception $e) {
    echo "   ❌ Creation test failed: " . $e->getMessage() . "\n\n";
}

echo "=== END OF TESTING ===\n";
