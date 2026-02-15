<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Direct Database Query Test ===\n\n";

$query = "
    SELECT 
        i.reference_number,
        i.destination as direct_destination,
        i.device_retrieval_id,
        d.name as destination_name
    FROM invoices i
    LEFT JOIN device_retrievals dr ON i.device_retrieval_id = dr.id
    LEFT JOIN destinations d ON dr.destination_id = d.id
    WHERE i.overstay_days > 0
    LIMIT 10
";

$results = DB::select($query);

echo "Found: " . count($results) . " results\n\n";

foreach ($results as $row) {
    echo "Invoice: {$row->reference_number}\n";
    echo "  Direct Destination: {$row->direct_destination}\n";
    echo "  Device Retrieval ID: " . ($row->device_retrieval_id ?? 'NULL') . "\n";
    echo "  Destination (from relation): " . ($row->destination_name ?? 'NULL') . "\n";
    echo "\n";
}

echo "=== Test Complete ===\n";
