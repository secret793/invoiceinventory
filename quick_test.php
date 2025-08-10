<?php
// Quick Test Script - Run with: php artisan tinker < quick_test.php

use App\Models\Device;
use App\Models\DeviceRetrieval;
use Carbon\Carbon;

echo "=== Quick Overstay Test ===\n";

// Create a simple test device
$device = Device::firstOrCreate([
    'device_id' => 'QUICK-TEST-001'
], [
    'device_id' => 'QUICK-TEST-001',
    'status' => 'ACTIVE'
]);

echo "Device: {$device->device_id} (ID: {$device->id})\n";

// Delete existing test record if any
DeviceRetrieval::where('device_id', $device->id)->delete();

// Create DeviceRetrieval with 3 days overstay
$deviceRetrieval = DeviceRetrieval::create([
    'date' => now(),
    'device_id' => $device->id,
    'boe' => 'BOE-QUICK-001',
    'sad_number' => 'SAD-QUICK-001',
    'vehicle_number' => 'VEH-QUICK-001',
    'regime' => 'TRANSIT',
    'destination' => 'Soma',
    'affixing_date' => now()->subDays(4), // 4 days ago = 3 days overstay for normal route
    'long_route_id' => null, // Normal route (1 day grace period)
    'agency' => 'Quick Test Agency',
    'agent_contact' => '1234567890',
    'truck_number' => 'TRUCK-QUICK',
    'driver_name' => 'Quick Test Driver',
    'retrieval_status' => 'NOT_RETRIEVED',
    'transfer_status' => 'pending',
    'payment_status' => 'PP'
]);

echo "DeviceRetrieval created: ID {$deviceRetrieval->id}\n";
echo "Affixing Date: {$deviceRetrieval->affixing_date}\n";

// Calculate expected values
$gracePeriod = $deviceRetrieval->long_route_id ? 2 : 1;
$daysDiff = now()->startOfDay()->diffInDays($deviceRetrieval->affixing_date->startOfDay());
$expectedOverstayDays = max(0, $daysDiff - $gracePeriod);
$expectedAmount = $expectedOverstayDays > 1 ? ($expectedOverstayDays - 1) * 1000 : 0;

echo "Days since affixing: {$daysDiff}\n";
echo "Grace period: {$gracePeriod} day(s)\n";
echo "Expected overstay days: {$expectedOverstayDays}\n";
echo "Expected overstay amount: D{$expectedAmount}\n";

// Check current values
echo "\nCurrent values:\n";
echo "Current overstay days: " . ($deviceRetrieval->overstay_days ?? 'NULL') . "\n";
echo "Current overstay amount: D" . ($deviceRetrieval->overstay_amount ?? '0') . "\n";

// Update using trait method
echo "\nUpdating using trait method...\n";
$deviceRetrieval->updateOverstayDays();
$deviceRetrieval->updateOverstayAmount();
$deviceRetrieval->save();

echo "After trait update:\n";
echo "Overstay days: {$deviceRetrieval->overstay_days}\n";
echo "Overstay amount: D{$deviceRetrieval->overstay_amount}\n";

// Test the calculation manually
echo "\nManual calculation test:\n";
$manualOverstayDays = $deviceRetrieval->calculateOverstayDays();
$manualOverstayAmount = $deviceRetrieval->calculateOverstayAmount($manualOverstayDays);
echo "Manual overstay days: {$manualOverstayDays}\n";
echo "Manual overstay amount: D{$manualOverstayAmount}\n";

echo "\n=== Test Complete ===\n";
echo "Run 'php artisan debug:overstay-days QUICK-TEST-001' for detailed debug info\n";
