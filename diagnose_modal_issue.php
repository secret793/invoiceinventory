<?php

/**
 * Modal Issue Diagnostic Script
 * Run with: php diagnose_modal_issue.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================================\n";
echo "MODAL ISSUE DIAGNOSTIC SCRIPT\n";
echo "=================================================\n\n";

// 1. Check Build Assets
echo "1. CHECKING FRONTEND ASSETS\n";
echo "----------------------------\n";
$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    echo "✅ Manifest exists: $manifestPath\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    echo "   Assets found: " . count($manifest) . "\n";
    echo "   Last modified: " . date('Y-m-d H:i:s', filemtime($manifestPath)) . "\n";
    
    // Check for Filament assets
    $hasFilamentAssets = false;
    foreach ($manifest as $key => $value) {
        if (strpos($key, 'filament') !== false || strpos($key, 'app') !== false) {
            echo "   - $key => {$value['file']}\n";
            $hasFilamentAssets = true;
        }
    }
    
    if (!$hasFilamentAssets) {
        echo "⚠️  WARNING: No Filament-specific assets found in manifest!\n";
    }
} else {
    echo "❌ BUILD MANIFEST MISSING: $manifestPath\n";
    echo "   Run: npm run build\n";
}
echo "\n";

// 2. Check Cache Status
echo "2. CHECKING CACHE STATUS\n";
echo "-------------------------\n";
$cacheFiles = [
    'config' => base_path('bootstrap/cache/config.php'),
    'routes' => base_path('bootstrap/cache/routes-v7.php'),
    'views' => storage_path('framework/views'),
];

foreach ($cacheFiles as $type => $path) {
    if (file_exists($path)) {
        if (is_dir($path)) {
            $count = count(glob($path . '/*'));
            echo "⚠️  $type cache exists: $count files\n";
        } else {
            echo "⚠️  $type cache exists: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
        }
    } else {
        echo "✅ $type cache clear\n";
    }
}
echo "\n";

// 3. Check Livewire Configuration
echo "3. CHECKING LIVEWIRE CONFIGURATION\n";
echo "-----------------------------------\n";
$livewireAsset = config('livewire.asset_url');
echo "Livewire Asset URL: " . ($livewireAsset ?: 'default') . "\n";
echo "Livewire Update Mode: " . config('livewire.update_mode', 'default') . "\n";
echo "App Debug: " . (config('app.debug') ? 'enabled' : 'disabled') . "\n";
echo "App Env: " . config('app.env') . "\n";
echo "\n";

// 4. Check Modal Actions in ViewAssignmentDataEntry
echo "4. CHECKING MODAL ACTIONS\n";
echo "--------------------------\n";
$filePath = app_path('Filament/Resources/DataEntryAssignmentResource/Pages/ViewAssignmentDataEntry.php');
if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    
    $actions = [
        'Generate Receipt' => preg_match('/->label\([\'"]Generate Receipt[\'"]\)/', $content),
        'View Dispatch Report' => preg_match('/->label\([\'"]View Dispatch Report[\'"]\)/', $content),
        'Dispatch Device(s)' => preg_match('/->label\([\'"]Dispatch Device\(s\)[\'"]\)/', $content),
        'View Generated Receipt' => preg_match('/->label\([\'"]View Generated Receipt[\'"]\)/', $content),
    ];
    
    foreach ($actions as $action => $found) {
        echo ($found ? "✅" : "❌") . " $action action found\n";
    }
    
    // Check for unique action names
    preg_match_all('/Actions\\\\Action::make\([\'"]([^\'"]*)[\'"]\)/', $content, $matches);
    $actionNames = $matches[1];
    echo "\nAction names found: " . count($actionNames) . "\n";
    $duplicates = array_diff_assoc($actionNames, array_unique($actionNames));
    if (!empty($duplicates)) {
        echo "⚠️  DUPLICATE ACTION NAMES FOUND:\n";
        foreach (array_unique($duplicates) as $dup) {
            echo "   - $dup\n";
        }
    } else {
        echo "✅ All action names are unique\n";
    }
} else {
    echo "❌ ViewAssignmentDataEntry.php not found\n";
}
echo "\n";

// 5. Check Public Build Directory Permissions
echo "5. CHECKING FILE PERMISSIONS\n";
echo "-----------------------------\n";
$publicBuild = public_path('build');
if (file_exists($publicBuild)) {
    $perms = substr(sprintf('%o', fileperms($publicBuild)), -4);
    $owner = posix_getpwuid(fileowner($publicBuild))['name'] ?? 'unknown';
    echo "Build directory: $publicBuild\n";
    echo "Permissions: $perms\n";
    echo "Owner: $owner\n";
    
    // Check if readable
    echo "Readable: " . (is_readable($publicBuild) ? "✅" : "❌") . "\n";
} else {
    echo "❌ Build directory does not exist: $publicBuild\n";
}
echo "\n";

// 6. Check for Alpine.js/Livewire conflicts
echo "6. CHECKING VENDOR ASSETS\n";
echo "--------------------------\n";
$vendorPaths = [
    'Livewire' => base_path('vendor/livewire/livewire'),
    'Filament' => base_path('vendor/filament/filament'),
    'Alpine.js in Filament' => base_path('vendor/filament/support/resources/js'),
];

foreach ($vendorPaths as $name => $path) {
    echo ($name . ": " . (file_exists($path) ? "✅ exists" : "❌ missing") . "\n");
}
echo "\n";

// 7. Generate Recommendations
echo "7. RECOMMENDATIONS\n";
echo "-------------------\n";

$issues = [];

if (!file_exists($manifestPath)) {
    $issues[] = "Run: npm install && npm run build";
}

if (file_exists(base_path('bootstrap/cache/config.php'))) {
    $issues[] = "Clear config cache: php artisan config:clear";
}

if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
    $issues[] = "Clear route cache: php artisan route:clear";
}

$viewCacheDir = storage_path('framework/views');
if (file_exists($viewCacheDir) && count(glob($viewCacheDir . '/*')) > 0) {
    $issues[] = "Clear view cache: php artisan view:clear";
}

if (empty($issues)) {
    echo "✅ No immediate issues detected\n";
    echo "\nIf modals still show wrong content, try:\n";
    echo "1. Clear browser cache completely (Ctrl+Shift+Del)\n";
    echo "2. Check browser console for JavaScript errors (F12)\n";
    echo "3. Ensure each modal action has a UNIQUE name\n";
    echo "4. Add ->modalId('unique-id-here') to each action\n";
} else {
    echo "⚠️  ISSUES FOUND - Run these commands:\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". $issue\n";
    }
}

echo "\n=================================================\n";
echo "DIAGNOSTIC COMPLETE\n";
echo "=================================================\n";
