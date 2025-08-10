<?php
// Test Overstay Data Creation Script
// Run with: php artisan tinker < test_overstay_data.php

use App\Models\Device;
use App\Models\DeviceRetrieval;
use App\Models\Monitoring;
use Carbon\Carbon;

echo "=== Creating Test Data for Overstay Calculation ===\n";

// Test Case 1: 3 Days Overstay (Normal Route) - Should be D2000
echo "\n--- Test Case 1: 3 Days Overstay (Normal Route) ---\n";

// Create or find a device
$device1 = Device::firstOrCreate([
    'device_id' => 'TEST-OVERSTAY-001'
], [
    'device_id' => 'TEST-OVERSTAY-001',
    'status' => 'ACTIVE'
]);

echo "Device created: {$device1->device_id} (ID: {$device1->id})\n";

// Create DeviceRetrieval with affixing date 4 days ago (3 days overstay for normal route)
$deviceRetrieval1 = DeviceRetrieval::create([
    'date' => now(),
    'device_id' => $device1->id,
    'boe' => 'BOE-TEST-001',
    'sad_number' => 'SAD-TEST-001',
    'vehicle_number' => 'VEH-TEST-001',
    'regime' => 'TRANSIT',
    'destination' => 'Soma',
    'affixing_date' => now()->subDays(4), // 4 days ago
    'long_route_id' => null, // Normal route (1 day grace period)
    'agency' => 'Test Agency',
    'agent_contact' => '1234567890',
    'truck_number' => 'TRUCK-001',
    'driver_name' => 'Test Driver',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "DeviceRetrieval created: ID {$deviceRetrieval1->id}\n";
echo "Affixing Date: {$deviceRetrieval1->affixing_date}\n";
echo "Grace Period: 1 day (normal route)\n";
echo "Expected Overstay Days: 3 days\n";
echo "Expected Overstay Amount: D2000\n";

// Test Case 2: 5 Days Overstay (Long Route) - Should be D3000
echo "\n--- Test Case 2: 5 Days Overstay (Long Route) ---\n";

$device2 = Device::firstOrCreate([
    'device_id' => 'TEST-OVERSTAY-002'
], [
    'device_id' => 'TEST-OVERSTAY-002',
    'status' => 'ACTIVE'
]);

echo "Device created: {$device2->device_id} (ID: {$device2->id})\n";

// Create DeviceRetrieval with affixing date 7 days ago (5 days overstay for long route)
$deviceRetrieval2 = DeviceRetrieval::create([
    'date' => now(),
    'device_id' => $device2->id,
    'boe' => 'BOE-TEST-002',
    'sad_number' => 'SAD-TEST-002',
    'vehicle_number' => 'VEH-TEST-002',
    'regime' => 'TRANSIT',
    'destination' => 'Farefeni',
    'affixing_date' => now()->subDays(7), // 7 days ago
    'long_route_id' => 1, // Long route (2 days grace period)
    'agency' => 'Test Agency Long',
    'agent_contact' => '0987654321',
    'truck_number' => 'TRUCK-002',
    'driver_name' => 'Test Driver Long',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "DeviceRetrieval created: ID {$deviceRetrieval2->id}\n";
echo "Affixing Date: {$deviceRetrieval2->affixing_date}\n";
echo "Grace Period: 2 days (long route)\n";
echo "Expected Overstay Days: 5 days\n";
echo "Expected Overstay Amount: D4000\n";

// Test Case 3: No Overstay (Within Grace Period) - Should be D0
echo "\n--- Test Case 3: No Overstay (Within Grace Period) ---\n";

$device3 = Device::firstOrCreate([
    'device_id' => 'TEST-OVERSTAY-003'
], [
    'device_id' => 'TEST-OVERSTAY-003',
    'status' => 'ACTIVE'
]);

echo "Device created: {$device3->device_id} (ID: {$device3->id})\n";

$deviceRetrieval3 = DeviceRetrieval::create([
    'date' => now(),
    'device_id' => $device3->id,
    'boe' => 'BOE-TEST-003',
    'sad_number' => 'SAD-TEST-003',
    'vehicle_number' => 'VEH-TEST-003',
    'regime' => 'WAREHOUSE',
    'destination' => 'Ghana',
    'affixing_date' => now()->subHours(12), // 12 hours ago (within grace period)
    'long_route_id' => null, // Normal route
    'agency' => 'Test Agency No Overstay',
    'agent_contact' => '1122334455',
    'truck_number' => 'TRUCK-003',
    'driver_name' => 'Test Driver No Overstay',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "DeviceRetrieval created: ID {$deviceRetrieval3->id}\n";
echo "Affixing Date: {$deviceRetrieval3->affixing_date}\n";
echo "Grace Period: 1 day (normal route)\n";
echo "Expected Overstay Days: 0 days\n";
echo "Expected Overstay Amount: D0\n";

// Test Case 4: Using Monitoring Table for Affixing Date
echo "\n--- Test Case 4: Affixing Date from Monitoring Table ---\n";

$device4 = Device::firstOrCreate([
    'device_id' => 'TEST-OVERSTAY-004'
], [
    'device_id' => 'TEST-OVERSTAY-004',
    'status' => 'ACTIVE'
]);

echo "Device created: {$device4->device_id} (ID: {$device4->id})\n";

// Create DeviceRetrieval without affixing_date
$deviceRetrieval4 = DeviceRetrieval::create([
    'date' => now(),
    'device_id' => $device4->id,
    'boe' => 'BOE-TEST-004',
    'sad_number' => 'SAD-TEST-004',
    'vehicle_number' => 'VEH-TEST-004',
    'regime' => 'TRANSIT',
    'destination' => 'Soma',
    'affixing_date' => null, // No affixing date in DeviceRetrieval
    'long_route_id' => null,
    'agency' => 'Test Agency Monitoring',
    'agent_contact' => '5566778899',
    'truck_number' => 'TRUCK-004',
    'driver_name' => 'Test Driver Monitoring',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

// Create Monitoring record with affixing date 6 days ago
$monitoring4 = Monitoring::create([
    'device_id' => $device4->id,
    'affixing_date' => now()->subDays(6), // 6 days ago
    'current_date' => now(),
    'route_id' => null,
    'long_route_id' => null,
    'overdue_hours' => 0,
    'overstay_days' => 0
]);

echo "DeviceRetrieval created: ID {$deviceRetrieval4->id} (no affixing_date)\n";
echo "Monitoring created: ID {$monitoring4->id}\n";
echo "Monitoring Affixing Date: {$monitoring4->affixing_date}\n";
echo "Grace Period: 1 day (normal route)\n";
echo "Expected Overstay Days: 5 days\n";
echo "Expected Overstay Amount: D4000\n";

echo "\n=== Test Data Creation Complete ===\n";
echo "\nNext Steps:\n";
echo "1. Run: php artisan debug:overstay-days\n";
echo "2. Run: php artisan app:update-overdue-hours\n";
echo "3. Check the results!\n";

// Display summary
echo "\n=== Test Cases Summary ===\n";
echo "Device ID: TEST-OVERSTAY-001 | Expected: 3 days overstay, D2000\n";
echo "Device ID: TEST-OVERSTAY-002 | Expected: 5 days overstay, D4000\n";
echo "Device ID: TEST-OVERSTAY-003 | Expected: 0 days overstay, D0\n";
echo "Device ID: TEST-OVERSTAY-004 | Expected: 5 days overstay, D4000 (from monitoring)\n";
