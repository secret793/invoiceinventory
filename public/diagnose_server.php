<?php
/**
 * Laravel Apache Routing Diagnostic Script
 * Upload this file to your public directory and access it directly
 * URL: https://etracking-gambia.gm/diagnose_server.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Server Diagnostic Report</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #333; }
        .success { border-left-color: #28a745; }
        .warning { border-left-color: #ffc107; }
        .error { border-left-color: #dc3545; }
        h2 { margin-top: 0; }
        .status { font-weight: bold; padding: 5px 10px; border-radius: 3px; }
        .status.ok { background: #d4edda; color: #155724; }
        .status.warn { background: #fff3cd; color: #856404; }
        .status.fail { background: #f8d7da; color: #721c24; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Laravel Server Diagnostic Report</h1>
    <p><strong>Server:</strong> <?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></p>
    <p><strong>Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
    <hr>

<?php

// Test 1: Current Directory & Document Root
echo '<div class="section">';
echo '<h2>1️⃣ Document Root Check</h2>';
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Not Set';
$currentDir = __DIR__;
$scriptName = $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown';

echo "<p><strong>Document Root:</strong> <code class='code'>$docRoot</code></p>";
echo "<p><strong>This Script Location:</strong> <code class='code'>$currentDir</code></p>";
echo "<p><strong>Script Filename:</strong> <code class='code'>$scriptName</code></p>";

$isInPublic = (strpos($currentDir, '/public') !== false || strpos($currentDir, '\public') !== false);
if ($isInPublic || basename($currentDir) === 'public') {
    echo '<p class="status ok">✅ GOOD: Script is in public folder</p>';
} else {
    echo '<p class="status fail">❌ ERROR: Script should be in the public folder!</p>';
    echo '<p><strong>Solution:</strong> DocumentRoot must point to: <code class="code">/path/to/project/public</code></p>';
}
echo '</div>';

// Test 2: Check .htaccess
echo '<div class="section">';
echo '<h2>2️⃣ .htaccess File Check</h2>';
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    echo '<p class="status ok">✅ GOOD: .htaccess file exists</p>';
    echo '<p><strong>File Path:</strong> <code class="code">' . $htaccessPath . '</code></p>';
    
    if (is_readable($htaccessPath)) {
        echo '<p class="status ok">✅ GOOD: .htaccess is readable</p>';
        $htaccessContent = file_get_contents($htaccessPath);
        echo '<p><strong>Content Preview (first 500 chars):</strong></p>';
        echo '<pre>' . htmlspecialchars(substr($htaccessContent, 0, 500)) . '</pre>';
        
        if (strpos($htaccessContent, 'mod_rewrite') !== false) {
            echo '<p class="status ok">✅ GOOD: mod_rewrite directives found</p>';
        } else {
            echo '<p class="status warn">⚠️ WARNING: mod_rewrite directives not clearly visible</p>';
        }
    } else {
        echo '<p class="status fail">❌ ERROR: .htaccess exists but is not readable</p>';
        echo '<p><strong>Solution:</strong> Fix file permissions - chmod 644 .htaccess</p>';
    }
} else {
    echo '<p class="status fail">❌ ERROR: .htaccess file NOT found!</p>';
    echo '<p><strong>Solution:</strong> Create .htaccess file in public folder</p>';
}
echo '</div>';

// Test 3: mod_rewrite Check
echo '<div class="section">';
echo '<h2>3️⃣ Apache mod_rewrite Check</h2>';
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo '<p class="status ok">✅ GOOD: mod_rewrite is enabled</p>';
    } else {
        echo '<p class="status fail">❌ ERROR: mod_rewrite is NOT enabled</p>';
        echo '<p><strong>Solution:</strong> Run: <code class="code">sudo a2enmod rewrite && sudo systemctl restart apache2</code></p>';
    }
} else {
    echo '<p class="status warn">⚠️ UNKNOWN: Cannot check (apache_get_modules not available)</p>';
    echo '<p>This is common in FastCGI/FPM mode. Check manually:</p>';
    echo '<pre>apache2ctl -M | grep rewrite</pre>';
}
echo '</div>';

// Test 4: PHP Version
echo '<div class="section">';
echo '<h2>4️⃣ PHP Version Check</h2>';
$phpVersion = phpversion();
echo "<p><strong>PHP Version:</strong> <code class='code'>$phpVersion</code></p>";
if (version_compare($phpVersion, '8.0.0', '>=')) {
    echo '<p class="status ok">✅ GOOD: PHP version is compatible with Laravel 9+</p>';
} elseif (version_compare($phpVersion, '7.4.0', '>=')) {
    echo '<p class="status warn">⚠️ OK: PHP 7.4+ works but consider upgrading</p>';
} else {
    echo '<p class="status fail">❌ ERROR: PHP version too old for modern Laravel</p>';
}
echo '</div>';

// Test 5: Required PHP Extensions
echo '<div class="section">';
echo '<h2>5️⃣ Required PHP Extensions</h2>';
$requiredExtensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
$missing = [];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✅ <code class='code'>$ext</code> - Loaded</p>";
    } else {
        echo "<p>❌ <code class='code'>$ext</code> - MISSING</p>";
        $missing[] = $ext;
    }
}
if (empty($missing)) {
    echo '<p class="status ok">✅ GOOD: All required extensions are loaded</p>';
} else {
    echo '<p class="status fail">❌ ERROR: Missing extensions: ' . implode(', ', $missing) . '</p>';
}
echo '</div>';

// Test 6: File Permissions
echo '<div class="section">';
echo '<h2>6️⃣ File Permissions Check</h2>';
$indexPath = __DIR__ . '/index.php';
if (file_exists($indexPath)) {
    echo '<p class="status ok">✅ GOOD: index.php exists</p>';
    $perms = substr(sprintf('%o', fileperms($indexPath)), -4);
    echo "<p><strong>index.php permissions:</strong> <code class='code'>$perms</code></p>";
    
    if (is_readable($indexPath)) {
        echo '<p class="status ok">✅ GOOD: index.php is readable by web server</p>';
    } else {
        echo '<p class="status fail">❌ ERROR: index.php is not readable</p>';
    }
} else {
    echo '<p class="status fail">❌ ERROR: index.php NOT found in public folder!</p>';
}

// Check parent directories
$parentDir = dirname(__DIR__);
echo "<p><strong>Project Root:</strong> <code class='code'>$parentDir</code></p>";
echo '</div>';

// Test 7: Environment Check
echo '<div class="section">';
echo '<h2>7️⃣ Laravel Environment Check</h2>';
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    echo '<p class="status ok">✅ GOOD: .env file exists</p>';
    if (is_readable($envPath)) {
        echo '<p class="status ok">✅ GOOD: .env is readable</p>';
    } else {
        echo '<p class="status warn">⚠️ WARNING: .env exists but not readable</p>';
    }
} else {
    echo '<p class="status fail">❌ ERROR: .env file NOT found!</p>';
    echo '<p><strong>Solution:</strong> Copy .env.example to .env</p>';
}

$vendorPath = dirname(__DIR__) . '/vendor';
if (is_dir($vendorPath)) {
    echo '<p class="status ok">✅ GOOD: vendor directory exists</p>';
} else {
    echo '<p class="status fail">❌ ERROR: vendor directory NOT found!</p>';
    echo '<p><strong>Solution:</strong> Run: <code class="code">composer install</code></p>';
}
echo '</div>';

// Test 8: Server Variables
echo '<div class="section">';
echo '<h2>8️⃣ Important Server Variables</h2>';
echo '<pre>';
echo 'SERVER_SOFTWARE: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not Set') . "\n";
echo 'REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Not Set') . "\n";
echo 'SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'Not Set') . "\n";
echo 'PHP_SELF: ' . ($_SERVER['PHP_SELF'] ?? 'Not Set') . "\n";
echo 'GATEWAY_INTERFACE: ' . ($_SERVER['GATEWAY_INTERFACE'] ?? 'Not Set') . "\n";
echo '</pre>';
echo '</div>';

// Test 9: AllowOverride Test
echo '<div class="section">';
echo '<h2>9️⃣ AllowOverride Test</h2>';
echo '<p>This tests if .htaccess files are being processed:</p>';

// Check if a test rewrite would work
$testFile = __DIR__ . '/.htaccess';
if (file_exists($testFile)) {
    echo '<p class="status ok">✅ .htaccess exists - if you can access this page directly, basic processing works</p>';
    echo '<p><strong>Next Test:</strong> Try accessing a non-existent route:</p>';
    echo '<p><code class="code">https://etracking-gambia.gm/test-route-12345</code></p>';
    echo '<p>If you see Laravel error page → Routing works ✅</p>';
    echo '<p>If you see Apache 404 → AllowOverride issue ❌</p>';
} else {
    echo '<p class="status fail">❌ Cannot test - .htaccess missing</p>';
}
echo '</div>';

// Summary & Recommendations
echo '<div class="section">';
echo '<h2>📋 Summary & Next Steps</h2>';
echo '<p><strong>Ask your hosting provider to verify:</strong></p>';
echo '<ol>';
echo '<li><strong>Document Root:</strong> Must point to <code class="code">/full/path/to/invoiceinventory/public</code></li>';
echo '<li><strong>AllowOverride:</strong> Must be set to <code class="code">All</code> in Apache config</li>';
echo '<li><strong>mod_rewrite:</strong> Must be enabled</li>';
echo '<li><strong>File Permissions:</strong> Ensure Apache can read all files</li>';
echo '</ol>';

echo '<p><strong>Configuration they need to check:</strong></p>';
echo '<pre>';
echo '&lt;VirtualHost *:443&gt;
    ServerName etracking-gambia.gm
    DocumentRoot /full/path/to/invoiceinventory/public
    
    &lt;Directory /full/path/to/invoiceinventory/public&gt;
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    &lt;/Directory&gt;
    
    SSLEngine on
    # ... SSL certificates ...
&lt;/VirtualHost&gt;';
echo '</pre>';
echo '</div>';

?>

<div class="section success">
    <h2>✅ Diagnostic Complete</h2>
    <p>Save this report and send it to your hosting provider.</p>
    <p><strong>Test completed at:</strong> <?= date('Y-m-d H:i:s') ?></p>
</div>

</body>
</html>
