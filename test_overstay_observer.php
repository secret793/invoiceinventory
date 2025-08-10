<?php
// Test Overstay Observer - Run with: php artisan tinker < test_overstay_observer.php

use App\Models\Device;
use App\Models\DeviceRetrieval;
use Carbon\Carbon;

echo "=== Testing Overstay Observer ===\n";

// Clean up existing test data
echo "Cleaning up existing test data...\n";
$testDevices = Device::where('device_id', 'like', 'OVERSTAY-TEST-%')->get();
foreach ($testDevices as $device) {
    DeviceRetrieval::where('device_id', $device->id)->delete();
    $device->delete();
}

echo "\n--- Test Case 1: 1 Day Overstay = D1000 ---\n";
$device1 = Device::create([
    'device_id' => 'OVERSTAY-TEST-001',
    'status' => 'ACTIVE'
]);

$deviceRetrieval1 = DeviceRetrieval::create([
    'date' => now()->subDays(2), // 2 days ago
    'device_id' => $device1->id,
    'boe' => 'BOE-OBS-001',
    'sad_number' => 'SAD-OBS-001',
    'vehicle_number' => 'VEH-OBS-001',
    'regime' => 'TRANSIT',
    'destination' => 'Soma',
    'affixing_date' => now()->subDays(2), // 2 days ago = 1 day overstay (normal route)
    'long_route_id' => null, // Normal route (1 day grace)
    'agency' => 'Test Agency',
    'agent_contact' => '1234567890',
    'truck_number' => 'TRUCK-001',
    'driver_name' => 'Test Driver',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "Device: {$device1->device_id}\n";
echo "Affixing Date: {$deviceRetrieval1->affixing_date}\n";
echo "Days since affixing: " . now()->startOfDay()->diffInDays($deviceRetrieval1->affixing_date->startOfDay()) . "\n";
echo "Grace period: 1 day (normal route)\n";
echo "Expected overstay: 1 day\n";
echo "Expected amount: D1000\n";
echo "Actual overstay days: {$deviceRetrieval1->overstay_days}\n";
echo "Actual amount: D{$deviceRetrieval1->overstay_amount}\n";
echo "✅ " . ($deviceRetrieval1->overstay_days == 1 && $deviceRetrieval1->overstay_amount == 1000 ? "PASS" : "FAIL") . "\n";

echo "\n--- Test Case 2: 3 Days Overstay = D3000 ---\n";
$device2 = Device::create([
    'device_id' => 'OVERSTAY-TEST-002',
    'status' => 'ACTIVE'
]);

$deviceRetrieval2 = DeviceRetrieval::create([
    'date' => now()->subDays(4),
    'device_id' => $device2->id,
    'boe' => 'BOE-OBS-002',
    'sad_number' => 'SAD-OBS-002',
    'vehicle_number' => 'VEH-OBS-002',
    'regime' => 'TRANSIT',
    'destination' => 'Farefeni',
    'affixing_date' => now()->subDays(4), // 4 days ago = 3 days overstay (normal route)
    'long_route_id' => null, // Normal route
    'agency' => 'Test Agency 2',
    'agent_contact' => '0987654321',
    'truck_number' => 'TRUCK-002',
    'driver_name' => 'Test Driver 2',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "Device: {$device2->device_id}\n";
echo "Affixing Date: {$deviceRetrieval2->affixing_date}\n";
echo "Days since affixing: " . now()->startOfDay()->diffInDays($deviceRetrieval2->affixing_date->startOfDay()) . "\n";
echo "Grace period: 1 day (normal route)\n";
echo "Expected overstay: 3 days\n";
echo "Expected amount: D3000\n";
echo "Actual overstay days: {$deviceRetrieval2->overstay_days}\n";
echo "Actual amount: D{$deviceRetrieval2->overstay_amount}\n";
echo "✅ " . ($deviceRetrieval2->overstay_days == 3 && $deviceRetrieval2->overstay_amount == 3000 ? "PASS" : "FAIL") . "\n";

echo "\n--- Test Case 3: Long Route (2 days grace) ---\n";
$device3 = Device::create([
    'device_id' => 'OVERSTAY-TEST-003',
    'status' => 'ACTIVE'
]);

$deviceRetrieval3 = DeviceRetrieval::create([
    'date' => now()->subDays(5),
    'device_id' => $device3->id,
    'boe' => 'BOE-OBS-003',
    'sad_number' => 'SAD-OBS-003',
    'vehicle_number' => 'VEH-OBS-003',
    'regime' => 'TRANSIT',
    'destination' => 'Banjul',
    'affixing_date' => now()->subDays(5), // 5 days ago = 3 days overstay (long route)
    'long_route_id' => 1, // Long route (2 days grace)
    'agency' => 'Test Agency 3',
    'agent_contact' => '1122334455',
    'truck_number' => 'TRUCK-003',
    'driver_name' => 'Test Driver 3',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "Device: {$device3->device_id}\n";
echo "Affixing Date: {$deviceRetrieval3->affixing_date}\n";
echo "Days since affixing: " . now()->startOfDay()->diffInDays($deviceRetrieval3->affixing_date->startOfDay()) . "\n";
echo "Grace period: 2 days (long route)\n";
echo "Expected overstay: 3 days\n";
echo "Expected amount: D3000\n";
echo "Actual overstay days: {$deviceRetrieval3->overstay_days}\n";
echo "Actual amount: D{$deviceRetrieval3->overstay_amount}\n";
echo "✅ " . ($deviceRetrieval3->overstay_days == 3 && $deviceRetrieval3->overstay_amount == 3000 ? "PASS" : "FAIL") . "\n";

echo "\n--- Test Case 4: No Overstay (Within Grace Period) ---\n";
$device4 = Device::create([
    'device_id' => 'OVERSTAY-TEST-004',
    'status' => 'ACTIVE'
]);

$deviceRetrieval4 = DeviceRetrieval::create([
    'date' => now()->subHours(12),
    'device_id' => $device4->id,
    'boe' => 'BOE-OBS-004',
    'sad_number' => 'SAD-OBS-004',
    'vehicle_number' => 'VEH-OBS-004',
    'regime' => 'WAREHOUSE',
    'destination' => 'Ghana',
    'affixing_date' => now()->subHours(12), // 12 hours ago (within grace period)
    'long_route_id' => null, // Normal route
    'agency' => 'Test Agency 4',
    'agent_contact' => '5566778899',
    'truck_number' => 'TRUCK-004',
    'driver_name' => 'Test Driver 4',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "Device: {$device4->device_id}\n";
echo "Affixing Date: {$deviceRetrieval4->affixing_date}\n";
echo "Hours since affixing: " . now()->diffInHours($deviceRetrieval4->affixing_date) . "\n";
echo "Grace period: 1 day (normal route)\n";
echo "Expected overstay: 0 days\n";
echo "Expected amount: D0\n";
echo "Actual overstay days: {$deviceRetrieval4->overstay_days}\n";
echo "Actual amount: D{$deviceRetrieval4->overstay_amount}\n";
echo "✅ " . ($deviceRetrieval4->overstay_days == 0 && $deviceRetrieval4->overstay_amount == 0 ? "PASS" : "FAIL") . "\n";

echo "\n--- Test Case 5: Testing Manual Date Update ---\n";
echo "Updating affixing_date for device OVERSTAY-TEST-001 to 6 days ago...\n";

$deviceRetrieval1->update([
    'affixing_date' => now()->subDays(6) // Change to 6 days ago = 5 days overstay
]);

$deviceRetrieval1->refresh();

echo "New affixing date: {$deviceRetrieval1->affixing_date}\n";
echo "Expected overstay: 5 days\n";
echo "Expected amount: D5000\n";
echo "Actual overstay days: {$deviceRetrieval1->overstay_days}\n";
echo "Actual amount: D{$deviceRetrieval1->overstay_amount}\n";
echo "✅ " . ($deviceRetrieval1->overstay_days == 5 && $deviceRetrieval1->overstay_amount == 5000 ? "PASS" : "FAIL") . "\n";

echo "\n=== Observer Test Complete ===\n";
echo "\nSummary:\n";
echo "- Observer automatically calculates overstay days and amounts\n";
echo "- Triggers on create and update of relevant fields\n";
echo "- Business rule: D1000 per day of overstay\n";
echo "- Grace periods: 1 day (normal route), 2 days (long route)\n";
echo "\nNext: Test manual database updates to verify observer triggers!\n";
