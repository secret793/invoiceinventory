<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing affixed_by column functionality...\n\n";

// Check if the column exists in the database
try {
    $columns = DB::select("DESCRIBE confirmed_affix_logs");
    $affixedByColumnExists = false;

    echo "Columns in confirmed_affix_logs table:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
        if ($column->Field === 'affixed_by') {
            $affixedByColumnExists = true;
        }
    }

    if (!$affixedByColumnExists) {
        echo "\n❌ ERROR: affixed_by column does not exist in the database!\n";
        echo "Please run: php artisan migrate\n";
        exit(1);
    }

    echo "\n✅ affixed_by column exists in the database.\n\n";

} catch (Exception $e) {
    echo "❌ Error checking database schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if we can create a test record
try {
    // Check current user authentication
    $user = auth()->user();
    if (!$user) {
        echo "❌ No authenticated user found. Testing with user ID 1...\n";
        $testUserId = 1;
    } else {
        echo "✅ Authenticated user: {$user->name} (ID: {$user->id})\n";
        $testUserId = $user->id;
    }

    // Check if user exists
    $userExists = DB::table('users')->where('id', $testUserId)->exists();
    if (!$userExists) {
        echo "❌ User with ID {$testUserId} does not exist in database.\n";
        // Try to find any user
        $anyUser = DB::table('users')->first();
        if ($anyUser) {
            $testUserId = $anyUser->id;
            echo "Using user ID {$testUserId} for testing instead.\n";
        } else {
            echo "❌ No users found in database. Cannot test.\n";
            exit(1);
        }
    }

    echo "\n📝 Creating test ConfirmedAffixLog record...\n";

    // Create a test record
    $testRecord = \App\Models\ConfirmedAffixLog::create([
        'device_id' => 999999, // Test device ID
        'boe' => 'TEST-BOE-' . time(),
        'vehicle_number' => 'TEST-VEHICLE-' . time(),
        'regime' => 'TEST',
        'destination' => 'TEST DESTINATION',
        'agency' => 'TEST AGENCY',
        'affixing_date' => now(),
        'status' => 'AFFIXED',
        'affixed_by' => $testUserId,
    ]);

    echo "✅ Test record created with ID: {$testRecord->id}\n";
    echo "✅ affixed_by value: {$testRecord->affixed_by}\n";

    // Verify the record was saved correctly
    $savedRecord = \App\Models\ConfirmedAffixLog::find($testRecord->id);
    if ($savedRecord && $savedRecord->affixed_by == $testUserId) {
        echo "✅ Record verified: affixed_by was saved correctly!\n";

        // Test the relationship
        $affixedByUser = $savedRecord->affixedBy;
        if ($affixedByUser) {
            echo "✅ Relationship working: Record affixed by {$affixedByUser->name}\n";
        } else {
            echo "⚠️  Relationship issue: Could not load user from affixed_by\n";
        }
    } else {
        echo "❌ Record verification failed: affixed_by was not saved correctly!\n";
    }

    // Clean up test record
    $testRecord->delete();
    echo "\n🧹 Test record cleaned up.\n";

} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Testing completed.\n";
