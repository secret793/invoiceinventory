<?php
/**
 * Post-Deployment Health Check Script
 * Run on VPS after deployment to verify all systems are working
 * Usage: php health_check.php
 */

define('BASE_PATH', __DIR__);

echo "\n" . str_repeat("=", 80) . "\n";
echo "POST-DEPLOYMENT HEALTH CHECK\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n\n";

$checks = [];
$failed = 0;

// ===== 1. ENVIRONMENT & CONFIG =====
echo "[1/10] CHECKING ENVIRONMENT & CONFIGURATION\n";
echo str_repeat("-", 80) . "\n";

if (!file_exists(BASE_PATH . '/.env')) {
    echo "❌ .env file missing\n";
    $checks[] = ['name' => '.env file exists', 'status' => false];
    $failed++;
} else {
    echo "✅ .env file exists\n";
    $checks[] = ['name' => '.env file exists', 'status' => true];
    
    // Check .env permissions
    $perms = fileperms(BASE_PATH . '/.env');
    $readable = ($perms & 0x00000004) != 0;
    echo ($readable ? "✅" : "❌") . " .env is readable\n";
    $checks[] = ['name' => '.env is readable', 'status' => $readable];
    if (!$readable) $failed++;
}

echo "\n";

// ===== 2. DATABASE CONNECTION =====
echo "[2/10] CHECKING DATABASE CONNECTION\n";
echo str_repeat("-", 80) . "\n";

try {
    require BASE_PATH . '/vendor/autoload.php';
    $app = require BASE_PATH . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $pdo = DB::connection()->getPdo();
    if ($pdo) {
        echo "✅ Database connection successful\n";
        $checks[] = ['name' => 'Database connection', 'status' => true];
        
        // Check database name
        try {
            $db = DB::select("SELECT DATABASE()")[0]->{'DATABASE()'}; 
            echo "✅ Connected to database: $db\n";
        } catch (\Exception $e) {
            echo "⚠️ Could not verify database name\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    $checks[] = ['name' => 'Database connection', 'status' => false];
    $failed++;
}

echo "\n";

// ===== 3. KEY TABLES EXIST =====
echo "[3/10] CHECKING CRITICAL DATABASE TABLES\n";
echo str_repeat("-", 80) . "\n";

$requiredTables = ['invoices', 'users', 'system_settings'];
foreach ($requiredTables as $table) {
    try {
        $exists = DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->exists();
        
        echo ($exists ? "✅" : "❌") . " Table '$table' exists\n";
        $checks[] = ['name' => "Table '$table' exists", 'status' => $exists];
        if (!$exists) $failed++;
    } catch (\Exception $e) {
        echo "❌ Could not check table '$table': " . $e->getMessage() . "\n";
        $checks[] = ['name' => "Table '$table' exists", 'status' => false];
        $failed++;
    }
}

echo "\n";

// ===== 4. LARAVEL APP KEY =====
echo "[4/10] CHECKING LARAVEL CONFIGURATION\n";
echo str_repeat("-", 80) . "\n";

try {
    $appKey = config('app.key');
    if ($appKey && strlen($appKey) > 0) {
        echo "✅ APP_KEY is set\n";
        $checks[] = ['name' => 'APP_KEY configured', 'status' => true];
    } else {
        echo "❌ APP_KEY is empty or not set\n";
        $checks[] = ['name' => 'APP_KEY configured', 'status' => false];
        $failed++;
    }
    
    $appEnv = config('app.env');
    echo "✅ APP_ENV: $appEnv\n";
    
    $appDebug = config('app.debug') ? "true" : "false";
    echo "ℹ️  APP_DEBUG: $appDebug (should be 'false' in production)\n";
} catch (\Exception $e) {
    echo "❌ Could not read Laravel config: " . $e->getMessage() . "\n";
    $checks[] = ['name' => 'Laravel configuration', 'status' => false];
    $failed++;
}

echo "\n";

// ===== 5. FILE PERMISSIONS =====
echo "[5/10] CHECKING FILE PERMISSIONS\n";
echo str_repeat("-", 80) . "\n";

$permChecks = [
    'storage' => BASE_PATH . '/storage',
    'bootstrap/cache' => BASE_PATH . '/bootstrap/cache',
    'public' => BASE_PATH . '/public',
];

foreach ($permChecks as $name => $path) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        echo ($writable ? "✅" : "❌") . " $name is writable (perms: $perms)\n";
        $checks[] = ['name' => "$name is writable", 'status' => $writable];
        if (!$writable) $failed++;
    } else {
        echo "❌ $name directory does not exist\n";
        $checks[] = ['name' => "$name exists", 'status' => false];
        $failed++;
    }
}

echo "\n";

// ===== 6. FRONTEND BUILD ASSETS =====
echo "[6/10] CHECKING FRONTEND BUILD ASSETS\n";
echo str_repeat("-", 80) . "\n";

$manifestPath = BASE_PATH . '/public/build/manifest.json';
if (file_exists($manifestPath)) {
    echo "✅ Build manifest exists\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $assetCount = count($manifest);
    echo "✅ Assets in manifest: $assetCount\n";
    $checks[] = ['name' => 'Build manifest exists', 'status' => true];
    
    // Check if main app asset exists
    $hasApp = isset($manifest['resources/js/app.js']);
    echo ($hasApp ? "✅" : "❌") . " Main app.js asset found\n";
    $checks[] = ['name' => 'app.js asset compiled', 'status' => $hasApp];
    if (!$hasApp) $failed++;
} else {
    echo "❌ Build manifest not found at: $manifestPath\n";
    echo "⚠️  Run 'npm run build' on the server to compile assets\n";
    $checks[] = ['name' => 'Build manifest exists', 'status' => false];
    $failed++;
}

echo "\n";

// ===== 7. CACHE STATUS =====
echo "[7/10] CHECKING CACHE STATUS\n";
echo str_repeat("-", 80) . "\n";

$cacheDir = BASE_PATH . '/bootstrap/cache';
try {
    $configCached = file_exists($cacheDir . '/config.php');
    $routesCached = file_exists($cacheDir . '/routes-v7.php');
    $eventsCached = file_exists($cacheDir . '/events.php');
    $viewCached = file_exists($cacheDir . '/view.php');
    
    echo ($configCached ? "✅" : "ℹ️ ") . " Config cache: " . ($configCached ? "exists" : "not cached (will be generated on first load)") . "\n";
    echo ($routesCached ? "✅" : "ℹ️ ") . " Routes cache: " . ($routesCached ? "exists" : "not cached (will be generated on first load)") . "\n";
    echo ($viewCached ? "✅" : "ℹ️ ") . " View cache: " . ($viewCached ? "exists" : "not cached") . "\n";
    
    $checks[] = ['name' => 'Caches healthy', 'status' => true]; // Not a hard fail
} catch (\Exception $e) {
    echo "⚠️  Could not check cache status\n";
}

echo "\n";

// ===== 8. CRITICAL DIRECTORIES =====
echo "[8/10] CHECKING CRITICAL DIRECTORIES\n";
echo str_repeat("-", 80) . "\n";

$requiredDirs = ['app', 'config', 'routes', 'resources/views', 'database/migrations'];
foreach ($requiredDirs as $dir) {
    $fullPath = BASE_PATH . '/' . $dir;
    $exists = is_dir($fullPath);
    echo ($exists ? "✅" : "❌") . " $dir exists\n";
    $checks[] = ['name' => "$dir directory exists", 'status' => $exists];
    if (!$exists) $failed++;
}

echo "\n";

// ===== 9. PUBLIC INDEX.PHP =====
echo "[9/10] CHECKING PUBLIC ENTRY POINT\n";
echo str_repeat("-", 80) . "\n";

$indexPath = BASE_PATH . '/public/index.php';
if (file_exists($indexPath)) {
    echo "✅ public/index.php exists\n";
    $content = file_get_contents($indexPath, false, null, 0, 500);
    
    $isLaravel = strpos($content, 'bootstrap/app') !== false;
    $isWordPress = strpos($content, 'wp-blog-header') !== false;
    
    if ($isLaravel && !$isWordPress) {
        echo "✅ public/index.php is Laravel front controller\n";
        $checks[] = ['name' => 'public/index.php is Laravel front controller', 'status' => true];
    } elseif ($isWordPress) {
        echo "❌ public/index.php is WordPress file (should be Laravel)\n";
        $checks[] = ['name' => 'public/index.php is Laravel front controller', 'status' => false];
        $failed++;
    } else {
        echo "⚠️  public/index.php may not be correct Laravel entry point\n";
        $checks[] = ['name' => 'public/index.php is Laravel front controller', 'status' => false];
        $failed++;
    }
} else {
    echo "❌ public/index.php not found\n";
    $checks[] = ['name' => 'public/index.php exists', 'status' => false];
    $failed++;
}

echo "\n";

// ===== 10. LOG FILES =====
echo "[10/10] CHECKING LOG FILES\n";
echo str_repeat("-", 80) . "\n";

$logPath = BASE_PATH . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    echo "✅ Laravel log file exists\n";
    $size = filesize($logPath);
    $sizeMB = round($size / (1024 * 1024), 2);
    echo "ℹ️  Log file size: {$sizeMB} MB\n";
    
    // Check for recent errors
    $lastLines = shell_exec("tail -n 50 " . escapeshellarg($logPath) . " 2>/dev/null");
    if (strpos($lastLines, 'ERROR') !== false || strpos($lastLines, 'CRITICAL') !== false) {
        echo "⚠️  Recent errors detected in log file (see below)\n";
        echo "Last 10 log entries:\n";
        $lines = explode("\n", $lastLines);
        foreach (array_slice($lines, -10) as $line) {
            if (!empty(trim($line))) {
                echo "  " . $line . "\n";
            }
        }
    } else {
        echo "✅ No recent errors in log file\n";
    }
} else {
    echo "ℹ️  Laravel log file does not exist yet (will be created on first request)\n";
}

echo "\n";

// ===== SUMMARY =====
echo str_repeat("=", 80) . "\n";
echo "HEALTH CHECK SUMMARY\n";
echo str_repeat("=", 80) . "\n";

$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, fn($c) => $c['status']));

echo "Checks Passed: $passedChecks/$totalChecks\n";
echo "Checks Failed: $failed\n";

if ($failed > 0) {
    echo "\n⚠️  FAILED CHECKS:\n";
    foreach ($checks as $check) {
        if (!$check['status']) {
            echo "  ❌ " . $check['name'] . "\n";
        }
    }
}

echo "\n";

if ($failed === 0) {
    echo "✅ ALL CRITICAL CHECKS PASSED\n";
    echo "The application appears to be deployed correctly.\n";
} else {
    echo "❌ SOME CHECKS FAILED\n";
    echo "Please review the errors above and run:\n";
    echo "  1. php artisan optimize:clear\n";
    echo "  2. php artisan migrate (if needed)\n";
    echo "  3. npm run build (if front-end assets are missing)\n";
    echo "  4. sudo systemctl restart php8.3-fpm apache2\n";
}

echo "\n";
echo "Next Steps:\n";
echo "  1. Test the app: curl -I https://etracking-gambia.gm --insecure\n";
echo "  2. Check admin login at: https://etracking-gambia.gm/admin\n";
echo "  3. Tail logs for errors: tail -f storage/logs/laravel.log\n";
echo "\n";
?>
