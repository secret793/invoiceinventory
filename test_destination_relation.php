<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Destination ID in Device Retrievals ===\n\n";

$query = "
    SELECT 
        dr.id,
        dr.destination,
        dr.destination_id,
        d.name as dest_relation_name
    FROM device_retrievals dr
    LEFT JOIN destinations d ON dr.destination_id = d.id
    INNER JOIN invoices i ON dr.id = i.device_retrieval_id
    WHERE i.overstay_days > 0
    LIMIT 10
";

$results = DB::select($query);

echo "Found: " . count($results) . " device retrievals\n\n";

foreach ($results as $row) {
    echo "DR ID: {$row->id}\n";
    echo "  destination (string): {$row->destination}\n";
    echo "  destination_id (FK): " . ($row->destination_id ?? 'NULL') . "\n";
    echo "  Destination name (relation): " . ($row->dest_relation_name ?? 'NULL') . "\n";
    echo "\n";
}

// Check if destinations table has matching names
echo "\n=== Available Destinations in destinations table ===\n";
$destinations = DB::table('destinations')->orderBy('name')->get(['id', 'name']);
echo "Total: " . count($destinations) . "\n\n";
foreach ($destinations as $dest) {
    echo "[{$dest->id}] {$dest->name}\n";
}

echo "\n=== Test Complete ===\n";
