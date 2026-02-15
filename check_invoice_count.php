<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('invoices')->count();
$overstay = DB::table('invoices')->where('overstay_days', '>', 0)->count();

echo "Total invoices: {$count}\n";
echo "Overstay invoices: {$overstay}\n";

if ($overstay > 0) {
    $sample = DB::table('invoices')
        ->where('overstay_days', '>', 0)
        ->limit(3)
        ->get(['reference_number', 'destination', 'device_retrieval_id', 'overstay_days', 'status']);
    
    echo "\nSample records:\n";
    foreach ($sample as $inv) {
        echo "- {$inv->reference_number} | Dest: {$inv->destination} | DR_ID: {$inv->device_retrieval_id} | Days: {$inv->overstay_days} | Status: {$inv->status}\n";
    }
}
