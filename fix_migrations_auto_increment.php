<?php
/**
 * Fix migrations table: restore AUTO_INCREMENT on the id column.
 * Run once on the VPS: php fix_migrations_auto_increment.php
 * Delete after running.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking migrations table id column..." . PHP_EOL;

$col = DB::selectOne("SHOW COLUMNS FROM migrations WHERE Field = 'id'");

if (!$col) {
    echo "ERROR: migrations table not found." . PHP_EOL;
    exit(1);
}

echo "Current definition: Field={$col->Field}, Type={$col->Type}, Null={$col->Null}, Key={$col->Key}, Extra={$col->Extra}" . PHP_EOL;

if (stripos($col->Extra, 'auto_increment') !== false) {
    echo "AUTO_INCREMENT is already set — nothing to fix." . PHP_EOL;
    exit(0);
}

echo "AUTO_INCREMENT missing. Fixing now..." . PHP_EOL;

DB::statement("ALTER TABLE migrations MODIFY id bigint unsigned NOT NULL AUTO_INCREMENT");

// Verify
$col = DB::selectOne("SHOW COLUMNS FROM migrations WHERE Field = 'id'");
if (stripos($col->Extra, 'auto_increment') !== false) {
    echo "SUCCESS: AUTO_INCREMENT restored on migrations.id" . PHP_EOL;
    echo "You can now run: php artisan migrate --force" . PHP_EOL;
} else {
    echo "ERROR: Fix did not apply. Extra={$col->Extra}" . PHP_EOL;
    exit(1);
}
