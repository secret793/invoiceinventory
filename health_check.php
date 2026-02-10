<?php
/**
 * Comprehensive Post-Deployment Health Check Script
 * Run on VPS after deployment to verify all systems
 * Usage: php health_check.php
 */

define('BASE_PATH', __DIR__);
set_time_limit(30);

echo "\n" . str_repeat("=", 90) . "\n";
echo "                     COMPREHENSIVE HEALTH CHECK\n";
echo "                          Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 90) . "\n\n";

$checks = [];
$critical_failed = 0;

// ===== SYSTEM SERVICES =====
echo "[1/15] CHECKING SYSTEM SERVICES\n";
echo str_repeat("-", 90) . "\n";

$services = [
    'apache2' => 'Apache Web Server',
    'php8.3-fpm' => 'PHP-FPM',
    'mysql' => 'MySQL Database',
];

foreach ($services as $service => $label) {
    $status = shell_exec("systemctl is-active $service 2>&1");
    $isActive = strpos($status, 'active') !== false;
    echo ($isActive ? "✅" : "❌") . " $label ($service): " . trim($status) . "\n";
    $checks[] = ['name' => "$label is running", 'status' => $isActive, 'critical' => true];
    if (!$isActive) $critical_failed++;
}

echo "\n";

// ===== ENVIRONMENT & .ENV =====
echo "[2/15] CHECKING ENVIRONMENT CONFIGURATION\n";
echo str_repeat("-", 90) . "\n";

if (!file_exists(BASE_PATH . '/.env')) {
    echo "❌ .env file missing\n";
    $checks[] = ['name' => '.env exists', 'status' => false, 'critical' => true];
    $critical_failed++;
} else {
    echo "✅ .env file exists\n";
    $checks[] = ['name' => '.env exists', 'status' => true, 'critical' => true];
    
    $env = file_get_contents(BASE_PATH . '/.env');
    
    // Check critical env vars
    $envVars = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'APP_KEY', 'APP_URL'];
    foreach ($envVars as $var) {
        $hasVar = strpos($env, "$var=") !== false;
        echo ($hasVar ? "✅" : "❌") . " $var is defined\n";
        if (!$hasVar) {
            $checks[] = ['name' => "$var is set", 'status' => false, 'critical' => true];
            $critical_failed++;
        }
    }
}

echo "\n";

// ===== DIRECTORY STRUCTURE =====
echo "[3/15] CHECKING DIRECTORY STRUCTURE\n";
echo str_repeat("-", 90) . "\n";

$requiredDirs = [
    'app' => 'Application code',
    'config' => 'Configuration files',
    'routes' => 'Route definitions',
    'resources' => 'View/Asset resources',
    'database' => 'Database files',
    'public' => 'Web root',
    'storage' => 'Writable storage',
    'bootstrap' => 'Bootstrap cache',
    'vendor' => 'Composer packages',
];

foreach ($requiredDirs as $dir => $desc) {
    $fullPath = BASE_PATH . '/' . $dir;
    $exists = is_dir($fullPath);
    echo ($exists ? "✅" : "❌") . " $dir/: $desc\n";
    $checks[] = ['name' => "$dir/ exists", 'status' => $exists, 'critical' => true];
    if (!$exists) $critical_failed++;
}

echo "\n";

// ===== FILE PERMISSIONS =====
echo "[4/15] CHECKING FILE PERMISSIONS & OWNERSHIP\n";
echo str_repeat("-", 90) . "\n";

$permChecks = [
    'storage' => BASE_PATH . '/storage',
    'bootstrap/cache' => BASE_PATH . '/bootstrap/cache',
    'public' => BASE_PATH . '/public',
];

foreach ($permChecks as $name => $path) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        $stat = @stat($path);
        $owner = $stat ? $stat['uid'] : 'unknown';
        
        echo ($writable ? "✅" : "❌") . " $name: writable=".($writable ? 'yes' : 'no').", perms=$perms, uid=$owner\n";
        $checks[] = ['name' => "$name is writable", 'status' => $writable, 'critical' => true];
        if (!$writable) $critical_failed++;
    }
}

// Check .env permissions
$envPerms = fileperms(BASE_PATH . '/.env');
$envReadable = ($envPerms & 0x00000004) != 0;
echo ($envReadable ? "✅" : "❌") . " .env: readable, perms=" . substr(sprintf('%o', $envPerms), -4) . "\n";

echo "\n";

// ===== PUBLIC INDEX.PHP =====
echo "[5/15] CHECKING LARAVEL ENTRY POINT\n";
echo str_repeat("-", 90) . "\n";

$indexPath = BASE_PATH . '/public/index.php';
if (!file_exists($indexPath)) {
    echo "❌ public/index.php MISSING\n";
    $checks[] = ['name' => 'public/index.php exists', 'status' => false, 'critical' => true];
    $critical_failed++;
} else {
    echo "✅ public/index.php exists\n";
    $content = file_get_contents($indexPath, false, null, 0, 1000);
    
    $isLaravel = strpos($content, 'bootstrap/app') !== false;
    $isWordPress = strpos($content, 'wp-blog-header') !== false;
    
    if ($isWordPress) {
        echo "❌ ERROR: public/index.php is a WORDPRESS file (not Laravel)!\n";
        $checks[] = ['name' => 'Entry point is Laravel', 'status' => false, 'critical' => true];
        $critical_failed++;
    } elseif ($isLaravel) {
        echo "✅ public/index.php is correct Laravel front controller\n";
        $checks[] = ['name' => 'Entry point is Laravel', 'status' => true, 'critical' => true];
    } else {
        echo "⚠️  public/index.php may not be correct Laravel entry point\n";
        $checks[] = ['name' => 'Entry point is Laravel', 'status' => false, 'critical' => false];
    }
}

echo "\n";

// ===== DATABASE (Direct Connection, No Laravel Bootstrap) =====
echo "[6/15] CHECKING DATABASE CONNECTION\n";
echo str_repeat("-", 90) . "\n";

// Parse .env file properly (handles special chars and quotes)
function parseEnvFile($filePath) {
    $env = [];
    if (!file_exists($filePath)) return $env;
    
    $content = file_get_contents($filePath);
    
    // Split by newlines
    $lines = preg_split('/\r\n|\r|\n/', $content);
    
    foreach ($lines as $line) {
        // Trim and skip empty lines and comments
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') === false) continue;
        
        $pos = strpos($line, '=');
        $key = substr($line, 0, $pos);
        $value = substr($line, $pos + 1);
        
        $key = trim($key);
        $value = trim($value);
        
        // Remove surrounding quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        $env[$key] = $value;
    }
    return $env;
}

$env = parseEnvFile(BASE_PATH . '/.env');
$dbHost = $env['DB_HOST'] ?? '127.0.0.1';
$dbUser = $env['DB_USERNAME'] ?? '';
$dbPass = $env['DB_PASSWORD'] ?? '';
$dbName = $env['DB_DATABASE'] ?? '';

// Debug: Show what we parsed (for troubleshooting)
if (empty($dbUser) || empty($dbPass)) {
    echo "⚠️  WARNING: Database credentials may not be parsed correctly from .env\n";
    echo "  Parsed DB_USER: '$dbUser', DB_PASS: " . (empty($dbPass) ? "(empty)" : "***") . "\n";
}
$dbPass = $env['DB_PASSWORD'] ?? '';
$dbName = $env['DB_DATABASE'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=3306",
        $dbUser,
        $dbPass,
        [PDO::ATTR_TIMEOUT => 5]
    );
    echo "✅ MySQL connection successful (host: $dbHost, user: $dbUser)\n";
    $checks[] = ['name' => 'MySQL connection', 'status' => true, 'critical' => true];
    
    // Check database exists
    $result = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $dbExists = $result->rowCount() > 0;
    echo ($dbExists ? "✅" : "❌") . " Database '$dbName' exists\n";
    $checks[] = ['name' => "Database '$dbName' exists", 'status' => $dbExists, 'critical' => true];
    if (!$dbExists) $critical_failed++;
    
    // Check critical tables
    if ($dbExists) {
        $pdo->exec("USE `$dbName`");
        $requiredTables = ['invoices', 'users', 'system_settings'];
        
        foreach ($requiredTables as $table) {
            $result = $pdo->query("SHOW TABLES LIKE '$table'");
            $tableExists = $result->rowCount() > 0;
            echo ($tableExists ? "✅" : "❌") . " Table '$table' exists\n";
            if (!$tableExists) {
                $checks[] = ['name' => "Table '$table'", 'status' => false, 'critical' => false];
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL connection FAILED: " . $e->getMessage() . "\n";
    $checks[] = ['name' => 'MySQL connection', 'status' => false, 'critical' => true];
    $critical_failed++;
}

echo "\n";

// ===== FRONTEND BUILD ASSETS =====
echo "[7/15] CHECKING FRONTEND BUILD ASSETS\n";
echo str_repeat("-", 90) . "\n";

$manifestPath = BASE_PATH . '/public/build/manifest.json';
$buildDir = BASE_PATH . '/public/build';

if (!is_dir($buildDir)) {
    echo "❌ public/build directory does not exist\n";
    echo "⚠️  Run: npm install && npm run build\n";
    $checks[] = ['name' => 'Frontend build exists', 'status' => false, 'critical' => false];
} elseif (!file_exists($manifestPath)) {
    echo "❌ public/build/manifest.json not found\n";
    echo "⚠️  Run: npm run build\n";
    $checks[] = ['name' => 'Manifest exists', 'status' => false, 'critical' => false];
} else {
    echo "✅ public/build/manifest.json exists\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $assetCount = count($manifest ?? []);
    echo "✅ Assets in manifest: $assetCount\n";
    
    $hasApp = isset($manifest['resources/js/app.js']);
    echo ($hasApp ? "✅" : "⚠️ ") . " app.js compiled: " . ($hasApp ? "yes" : "no") . "\n";
    
    $checks[] = ['name' => 'Frontend assets compiled', 'status' => !empty($manifest), 'critical' => false];
}

// List some assets for debugging
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!empty($manifest)) {
        echo "\n  Sample assets in manifest:\n";
        $count = 0;
        foreach ($manifest as $src => $dst) {
            if ($count++ < 5) {
                $dstDisplay = is_array($dst) ? json_encode($dst) : $dst;
                echo "    • $src → $dstDisplay\n";
            }
        }
        if (count($manifest) > 5) {
            echo "    ... and " . (count($manifest) - 5) . " more\n";
        }
    }
}

echo "\n";

// ===== COMPOSER DEPENDENCIES =====
echo "[8/15] CHECKING COMPOSER & DEPENDENCIES\n";
echo str_repeat("-", 90) . "\n";

if (file_exists(BASE_PATH . '/composer.lock')) {
    echo "✅ composer.lock exists\n";
    $lockData = json_decode(file_get_contents(BASE_PATH . '/composer.lock'), true);
    $pkgCount = count($lockData['packages'] ?? []);
    echo "✅ Installed packages: $pkgCount\n";
} else {
    echo "❌ composer.lock not found\n";
    echo "⚠️  Run: composer install\n";
}

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    echo "✅ Composer autoload exists\n";
} else {
    echo "❌ vendor/autoload.php missing\n";
}

echo "\n";

// ===== PHP EXTENSIONS =====
echo "[9/15] CHECKING PHP EXTENSIONS\n";
echo str_repeat("-", 90) . "\n";

echo "PHP Version: " . phpversion() . "\n";

$requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'ctype', 'fileinfo'];
foreach ($requiredExt as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? "✅" : "❌") . " ext-$ext\n";
    if (!$loaded) {
        $checks[] = ['name' => "PHP ext-$ext", 'status' => false, 'critical' => true];
        $critical_failed++;
    }
}

echo "\n";

// ===== CACHE & CONFIGURATION =====
echo "[10/15] CHECKING LARAVEL CACHES\n";
echo str_repeat("-", 90) . "\n";

$cacheDir = BASE_PATH . '/bootstrap/cache';
$cacheFiles = [
    'config.php' => 'Config cache',
    'events.php' => 'Events cache',
    'packages.php' => 'Packages cache',
];

foreach ($cacheFiles as $file => $desc) {
    $exists = file_exists("$cacheDir/$file");
    $status = $exists ? "cached" : "not cached (will auto-generate)";
    echo "ℹ️  " . str_pad($desc, 30) . ": $status\n";
}

echo "\n";

// ===== LOG FILES =====
echo "[11/15] CHECKING LOG FILES\n";
echo str_repeat("-", 90) . "\n";

$logPath = BASE_PATH . '/storage/logs/laravel.log';
if (file_exists($logPath)) {
    echo "✅ laravel.log exists\n";
    $size = filesize($logPath);
    $sizeMB = round($size / (1024 * 1024), 2);
    echo "ℹ️  File size: {$sizeMB} MB\n";
    
    $lastMod = filemtime($logPath);
    $lastModTime = date('Y-m-d H:i:s', $lastMod);
    $minutesAgo = intval((time() - $lastMod) / 60);
    echo "ℹ️  Last modified: $lastModTime (${minutesAgo} minutes ago)\n";
    
    // Check for recent errors
    exec("tail -n 20 " . escapeshellarg($logPath) . " 2>/dev/null | grep -i 'error\\|fatal\\|exception' | wc -l", $output);
    $errorCount = intval($output[0] ?? 0);
    if ($errorCount > 0) {
        echo "⚠️  Found $errorCount error/fatal/exception entries in last 20 lines\n";
    } else {
        echo "✅ No recent errors in log tail\n";
    }
} else {
    echo "ℹ️  laravel.log not created yet (will be created on first request)\n";
}

echo "\n";

// ===== WEB ACCESSIBILITY (via curl) =====
echo "[12/15] CHECKING WEB SERVER ACCESS\n";
echo str_repeat("-", 90) . "\n";

$url = 'http://127.0.0.1/';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => true,
]);

$response = @curl_exec($ch);
$httpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    echo "ℹ️  Local server HTTP status: $httpCode\n";
    if ($httpCode >= 200 && $httpCode < 500) {
        echo "✅ Web server is responding\n";
    } elseif ($httpCode == 500) {
        echo "❌ Web server returning 500 error\n";
        $checks[] = ['name' => 'Web server responding', 'status' => false, 'critical' => false];
    }
} else {
    echo "⚠️  Could not connect to local server (may be normal if Apache not listening on 127.0.0.1)\n";
}

echo "\n";

// ===== ARTISAN COMMANDS AVAILABILITY =====
echo "[13/15] CHECKING ARTISAN COMMANDS\n";
echo str_repeat("-", 90) . "\n";

if (file_exists(BASE_PATH . '/artisan')) {
    echo "✅ artisan file exists\n";
    
    // Test artisan list (non-blocking timeout)
    exec("timeout 5 php " . escapeshellarg(BASE_PATH . '/artisan') . " list 2>&1", $output, $code);
    if ($code == 0) {
        echo "✅ artisan commands accessible\n";
    } else {
        echo "⚠️  artisan list returned exit code $code (may indicate bootstrap issue)\n";
    }
} else {
    echo "❌ artisan file not found\n";
}

echo "\n";

// ===== DISK SPACE =====
echo "[14/15] CHECKING DISK SPACE\n";
echo str_repeat("-", 90) . "\n";

$diskFree = disk_free_space(BASE_PATH);
$diskTotal = disk_total_space(BASE_PATH);
$diskUsedPercent = round(((($diskTotal - $diskFree) / $diskTotal)) * 100);

echo "Total: " . round($diskTotal / 1024 / 1024 / 1024, 2) . " GB\n";
echo "Free:  " . round($diskFree / 1024 / 1024 / 1024, 2) . " GB\n";
echo "Used:  " . $diskUsedPercent . "%\n";

if ($diskUsedPercent > 90) {
    echo "❌ WARNING: Disk nearly full!\n";
} elseif ($diskUsedPercent > 75) {
    echo "⚠️  Disk usage is high\n";
} else {
    echo "✅ Disk space adequate\n";
}

echo "\n";

// ===== RECOMMENDATION ENGINE =====
echo "[15/15] GENERATING RECOMMENDATIONS\n";
echo str_repeat("-", 90) . "\n";

$recommendations = [];

if ($critical_failed > 0) {
    $recommendations[] = "🔴 CRITICAL: Fix the " . $critical_failed . " failed critical checks above";
}

if (!file_exists(BASE_PATH . '/public/build/manifest.json')) {
    $recommendations[] = "🟡 Run frontend build: npm install && npm run build";
}

if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    $recommendations[] = "🟡 Install Composer dependencies: composer install";
}

if (isset($env) && empty($env['APP_KEY'])) {
    $recommendations[] = "🟡 Generate APP_KEY: php artisan key:generate";
}

if (empty($recommendations)) {
    $recommendations[] = "✅ No immediate recommendations; system appears healthy";
}

foreach ($recommendations as $rec) {
    echo "  " . $rec . "\n";
}

echo "\n";

// ===== SUMMARY =====
echo str_repeat("=", 90) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 90) . "\n";

$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, fn($c) => $c['status']));
$criticalChecks = array_filter($checks, fn($c) => $c['critical'] ?? false);
$criticalPassed = count(array_filter($criticalChecks, fn($c) => $c['status']));

echo "Total Checks: " . count($checks) . "\n";
echo "Passed: $passedChecks\n";
echo "Failed: " . ($totalChecks - $passedChecks) . "\n";
echo "\nCritical Checks: " . count($criticalChecks) . "\n";
echo "Critical Passed: $criticalPassed / " . count($criticalChecks) . "\n";

echo "\n";

if ($critical_failed === 0) {
    echo "✅ ✅ ✅  ALL CRITICAL CHECKS PASSED  ✅ ✅ ✅\n";
    echo "The application appears ready for use.\n";
} else {
    echo "❌ ❌ ❌  CRITICAL FAILURES DETECTED  ❌ ❌ ❌\n";
    echo "Please fix the issues above before accessing the application.\n";
    echo "\nFailed critical checks:\n";
    foreach ($checks as $check) {
        if (!$check['status'] && ($check['critical'] ?? false)) {
            echo "  ❌ " . $check['name'] . "\n";
        }
    }
}

echo "\n";
echo "NEXT STEPS:\n";
echo "  1. Review any FAILED checks above\n";
echo "  2. Follow recommendations in [15/15]\n";
echo "  3. Test locally: curl -I http://127.0.0.1/\n";
echo "  4. Test via domain: curl -I https://etracking-gambia.gm --insecure\n";
echo "  5. Monitor logs: tail -f storage/logs/laravel.log\n";
echo "  6. Access admin: https://etracking-gambia.gm/admin\n";
echo "\n";
echo str_repeat("=", 90) . "\n";
?>
