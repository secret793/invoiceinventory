<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$pending = DB::table('invoices')
    ->where('status', 'PP')
    ->where('overstay_days', '>', 0)
    ->limit(10)
    ->get(['reference_number', 'sad_boe', 'overstay_days', 'total_amount', 'created_at']);

echo "Pending Overstay Invoices:\n";
echo "==========================\n";
foreach ($pending as $inv) {
    echo "{$inv->reference_number} | {$inv->sad_boe} | Days: {$inv->overstay_days} | Amount: {$inv->total_amount} | {$inv->created_at}\n";
}

$count = DB::table('invoices')
    ->where('status', 'PP')
    ->where('overstay_days', '>', 0)
    ->count();
    
echo "\nTotal: {$count} pending overstay invoices\n";
