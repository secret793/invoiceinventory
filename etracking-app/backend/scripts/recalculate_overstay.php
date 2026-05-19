<?php

/**
 * Overstay Recalculation — Standalone Cron Script
 *
 * Purpose: Keeps device_retrievals.overstay_days / overstay_amount current
 *          by recalculating from affixing_date on every execution.
 *
 * Best practice: run every 30 minutes via Linux cron.
 *
 * Cron setup (as root or the PHP process user):
 *   crontab -e
 *   Add:  *\/30 * * * * php /path/to/etracking-app/backend/scripts/recalculate_overstay.php >> /var/log/overstay_cron.log 2>&1
 *
 * Note: The monitoring page also triggers a real-time recalculate on every
 * 10-second poll (via MonitoringController), so this cron is a safety net for
 * systems where the monitoring page is not always open.
 */

define('APP_ROOT', dirname(__DIR__));

// Bootstrap the autoloader
spl_autoload_register(function (string $class): void {
    $path = APP_ROOT . '/app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($path)) require_once $path;
});

// Load environment from DATABASE_URL (same logic as Database.php)
require_once APP_ROOT . '/app/Core/Database.php';
require_once APP_ROOT . '/app/Services/OverstayCalculatorService.php';

$start   = microtime(true);
$updated = \App\Services\OverstayCalculatorService::recalculateAll();
$elapsed = round((microtime(true) - $start) * 1000, 1);

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] Overstay recalculation complete — {$updated} record(s) processed in {$elapsed}ms\n";
