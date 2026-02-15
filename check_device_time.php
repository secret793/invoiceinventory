
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Receipt;
use App\Models\AssignToAgent;
use App\Models\Device;

echo "\n========================================\n";
echo "  Receipt & Device Time Check\n";
echo "========================================\n\n";

$receiptNumber = 'R-20260201-3670';

$receipt = Receipt::where('receipt_number', $receiptNumber)->first();

if (!$receipt) {
    echo "Receipt not found: {$receiptNumber}\n";
    exit;
}

echo "Receipt Number: {$receipt->receipt_number}\n";
echo "Receipt Date: " . ($receipt->date ? $receipt->date->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "Receipt Created At: {$receipt->created_at->format('Y-m-d H:i:s')}\n";
echo "\n";

$assignments = AssignToAgent::where('receipt_id', $receipt->id)
    ->with('device')
    ->get();

echo "Devices assigned to this receipt: {$assignments->count()}\n\n";

foreach ($assignments as $assignment) {
    echo "Device ID: {$assignment->device->device_id}\n";
    echo "  Assignment created_at: {$assignment->created_at->format('Y-m-d H:i:s')}\n";
    echo "  Device created_at: {$assignment->device->created_at->format('Y-m-d H:i:s')}\n";
    echo "  Device updated_at: {$assignment->device->updated_at->format('Y-m-d H:i:s')}\n";
    echo "\n";
    
    // Check time difference
    $deviceTime = $assignment->device->created_at;
    $receiptDate = $receipt->date ?? $receipt->created_at;
    $diffInMinutes = $deviceTime->diffInMinutes($receiptDate);
    $diffInSeconds = $deviceTime->diffInSeconds($receiptDate);
    
    echo "  Time Difference:\n";
    echo "    Difference: {$diffInMinutes} minutes ({$diffInSeconds} seconds)\n";
    echo "    Device is " . ($deviceTime->gt($receiptDate) ? "AFTER" : "BEFORE") . " receipt\n";
    echo "\n";
}

echo "========================================\n";
echo "ISSUE IDENTIFIED:\n";
echo "The 'device.created_at' timestamp shows when the\n";
echo "device was first added to the system, NOT when it\n";
echo "was dispatched on this receipt.\n\n";
echo "Receipt Date = When receipt was generated\n";
echo "Device Created At = When device record was created\n";
echo "Assignment Created At = When device was dispatched\n";
echo "========================================\n\n";
