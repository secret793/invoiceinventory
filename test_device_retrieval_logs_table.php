<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING DEVICE RETRIEVAL LOGS TABLE ===\n\n";

try {
    // Check if table exists
    if (Schema::hasTable('device_retrieval_logs')) {
        echo "✅ device_retrieval_logs table exists\n";

        // Check column structure
        $columns = DB::select("DESCRIBE device_retrieval_logs");
        echo "\n📋 Table Structure:\n";
        foreach ($columns as $column) {
            echo "   {$column->Field} ({$column->Type}) - {$column->Null} - {$column->Key}\n";
        }

        // Test model
        $count = \App\Models\DeviceRetrievalLog::count();
        echo "\n✅ DeviceRetrievalLog model working. Current records: {$count}\n";

        // Test relationships
        echo "\n🔗 Testing Relationships:\n";
        $log = new \App\Models\DeviceRetrievalLog();
        echo "   device() relationship: " . (method_exists($log, 'device') ? "✅" : "❌") . "\n";
        echo "   retrievedBy() relationship: " . (method_exists($log, 'retrievedBy') ? "✅" : "❌") . "\n";
        echo "   route() relationship: " . (method_exists($log, 'route') ? "✅" : "❌") . "\n";
        echo "   distributionPoint() relationship: " . (method_exists($log, 'distributionPoint') ? "✅" : "❌") . "\n";

        echo "\n🎉 DEVICE RETRIEVAL LOGS TABLE IS READY!\n";

    } else {
        echo "❌ device_retrieval_logs table does not exist!\n";
        echo "Please run the migration command:\n";
        echo "php artisan migrate --path=database/migrations/2025_08_03_223340_create_device_retrieval_logs_table.php\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
