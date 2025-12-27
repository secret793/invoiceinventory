// Run this in tinker: php artisan tinker

// Create a device
$device = new \App\Models\Device();
$device->device_type = 'JT701';
$device->device_id = 'TEST-' . rand(1000, 9999);
$device->serial_number = 'SN-' . rand(10000, 99999);
$device->batch_number = 'BATCH-' . date('Ymd');
$device->status = 'ACTIVE';
$device->date_received = now()->subDays(30);
$device->sim_number = '220' . rand(1000000, 9999999);
$device->sim_operator = 'AFRICELL';
$device->user_id = auth()->id() ?? 1;
$device->save();

echo "Device created with ID: " . $device->id . " and device_id: " . $device->device_id . "\n";

// Create a device retrieval with overstay days
$retrieval = new \App\Models\DeviceRetrieval();
$retrieval->device_id = $device->id;
$retrieval->date = now()->subDays(5);
$retrieval->affixing_date = now()->subDays(5); // 5 days ago
$retrieval->boe = 'BOE-' . rand(10000, 99999);
$retrieval->sad_number = 'SAD-' . rand(10000, 99999);
$retrieval->vehicle_number = 'VEH-' . rand(100, 999);
$retrieval->regime = 'TRANSIT';
$retrieval->destination = 'Soma';
$retrieval->agent = 'Test Agent';
$retrieval->agent_contact = '220' . rand(1000000, 9999999);
$retrieval->status = 'ACTIVE';
$retrieval->retrieval_status = 'PENDING';
$retrieval->overstay_days = 3; // 3 days overstay
$retrieval->overstay_amount = 3000; // D1000 per day
$retrieval->payment_status = 'PP'; // Pending Payment
$retrieval->save();

echo "Device Retrieval created with ID: " . $retrieval->id . "\n";
echo "Overstay days: " . $retrieval->overstay_days . "\n";
echo "Overstay amount: " . $retrieval->overstay_amount . "\n";

// Print a summary of what was created
echo "\nSUMMARY:\n";
echo "Device ID: " . $device->device_id . "\n";
echo "Serial Number: " . $device->serial_number . "\n";
echo "Status: " . $device->status . "\n";
echo "Retrieval BOE: " . $retrieval->boe . "\n";
echo "Retrieval SAD: " . $retrieval->sad_number . "\n";
echo "Overstay Days: " . $retrieval->overstay_days . "\n";
echo "Overstay Amount: D" . $retrieval->overstay_amount . "\n";
echo "Payment Status: " . $retrieval->payment_status . "\n";