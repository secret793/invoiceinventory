<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Updated Observer Functionality\n";
echo "=====================================\n";

// Check initial counts
$totalRecords = App\Models\DeviceRetrieval::count();
echo "Total DeviceRetrieval records: $totalRecords\n";

$notRetrievedRecords = App\Models\DeviceRetrieval::where('retrieval_status', 'NOT_RETRIEVED')->count();
echo "NOT_RETRIEVED records: $notRetrievedRecords\n";

$confirmedAffixLogsBefore = App\Models\ConfirmedAffixLog::count();
echo "ConfirmedAffixLog records (before): $confirmedAffixLogsBefore\n";

$deviceRetrievalLogsBefore = App\Models\DeviceRetrievalLog::count();
echo "DeviceRetrievalLog records (before): $deviceRetrievalLogsBefore\n";

// Test the observer by updating a record (if any exists)
if ($notRetrievedRecords > 0) {
    echo "\nTesting Updated Observer by updating first NOT_RETRIEVED record...\n";

    $record = App\Models\DeviceRetrieval::where('retrieval_status', 'NOT_RETRIEVED')->first();
    echo "Found record with device_id: " . ($record->device_id ?? 'NULL') . "\n";

    // Update the status to trigger observer
    $record->update(['retrieval_status' => 'RETRIEVED']);

    echo "Updated record to RETRIEVED status\n";

    // Check if both logs were created
    $confirmedAffixLogsAfter = App\Models\ConfirmedAffixLog::count();
    $deviceRetrievalLogsAfter = App\Models\DeviceRetrievalLog::count();

    echo "ConfirmedAffixLog records (after): $confirmedAffixLogsAfter (difference: " . ($confirmedAffixLogsAfter - $confirmedAffixLogsBefore) . ")\n";
    echo "DeviceRetrievalLog records (after): $deviceRetrievalLogsAfter (difference: " . ($deviceRetrievalLogsAfter - $deviceRetrievalLogsBefore) . ")\n";

    // Check the latest ConfirmedAffixLog record
    $latestConfirmedAffixLog = App\Models\ConfirmedAffixLog::latest()->first();
    if ($latestConfirmedAffixLog && $latestConfirmedAffixLog->device_id == $record->device_id) {
        echo "✓ ConfirmedAffixLog created for device_id: {$latestConfirmedAffixLog->device_id} with status: {$latestConfirmedAffixLog->status}\n";
    }

    // Check the latest DeviceRetrievalLog record
    $latestDeviceRetrievalLog = App\Models\DeviceRetrievalLog::latest()->first();
    if ($latestDeviceRetrievalLog && $latestDeviceRetrievalLog->device_id == $record->device_id) {
        echo "✓ DeviceRetrievalLog created for device_id: {$latestDeviceRetrievalLog->device_id} with action: {$latestDeviceRetrievalLog->action_type}\n";
    }

    // Test RETURN status as well
    echo "\nTesting RETURN status...\n";
    $record->update(['retrieval_status' => 'RETURNED']);

    $confirmedAffixLogsAfterReturn = App\Models\ConfirmedAffixLog::count();
    $deviceRetrievalLogsAfterReturn = App\Models\DeviceRetrievalLog::count();

    echo "ConfirmedAffixLog records (after return): $confirmedAffixLogsAfterReturn (difference: " . ($confirmedAffixLogsAfterReturn - $confirmedAffixLogsAfter) . ")\n";
    echo "DeviceRetrievalLog records (after return): $deviceRetrievalLogsAfterReturn (difference: " . ($deviceRetrievalLogsAfterReturn - $deviceRetrievalLogsAfter) . ")\n";

    // Revert the change
    $record->update(['retrieval_status' => 'NOT_RETRIEVED']);
    echo "\nReverted record back to NOT_RETRIEVED\n";
}

echo "\nDone!\n";
