<?php
/**
 * Security Scanner for Laravel Application
 * Scans for malware, backdoors, suspicious code patterns
 * Usage: php security_scan.php
 */

define('BASE_PATH', __DIR__);
define('RED', "\033[0;31m");
define('GREEN', "\033[0;32m");
define('YELLOW', "\033[1;33m");
define('NC', "\033[0m"); // No Color

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "              SECURITY SCANNER FOR LARAVEL APPLICATION\n";
echo "                    Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n\n";

$threats = [];
$suspicious = [];
$scannedFiles = 0;

// Malware signatures
$malwareSignatures = [
    'base64_decode' => 'Obfuscation (common in malware)',
    'eval(' => 'Code execution (dangerous)',
    'exec(' => 'System command execution',
    'shell_exec(' => 'Shell command execution',
    'system(' => 'System command execution',
    'passthru(' => 'Command execution',
    'proc_open(' => 'Process execution',
    'popen(' => 'Process execution',
    'wp-blog-header' => 'WordPress injection',
    'wp-cron' => 'WordPress malware',
    'wp-config' => 'WordPress files (suspicious in Laravel)',
    'wp-login' => 'WordPress login backdoor',
    'c99shell' => 'C99 web shell',
    'r57shell' => 'R57 web shell',
    'FilesMan' => 'File manager backdoor',
    'preg_replace.*\/e' => 'Deprecated preg_replace /e modifier (RCE)',
    'assert\(' => 'Assert execution (can be dangerous)',
    'create_function' => 'Dynamic function creation (deprecated, dangerous)',
    '\$_GET\[.*\]\(' => 'Variable function call from GET',
    '\$_POST\[.*\]\(' => 'Variable function call from POST',
    '\$\{.*\}\(' => 'Variable variable function call',
    'file_put_contents.*php://input' => 'Direct input to file write',
    'move_uploaded_file' => 'File upload (review manually)',
    'curl_exec' => 'External requests (review context)',
    'file_get_contents.*http' => 'Remote file inclusion',
    'readfile.*http' => 'Remote file read',
    'include.*http' => 'Remote file inclusion',
    'require.*http' => 'Remote file inclusion',
    '@include' => 'Suppressed include (suspicious)',
    '@require' => 'Suppressed require (suspicious)',
    'goto\s+\w+' => 'Goto statement (used in obfuscation)',
    'chr\(\d+\)' => 'Character encoding obfuscation',
    'gzinflate' => 'Compression obfuscation',
    'gzuncompress' => 'Compression obfuscation',
    'str_rot13' => 'ROT13 obfuscation',
    'convert_uudecode' => 'UUencode obfuscation',
];

// Directories to scan
$dirsToScan = [
    'public',
    'app',
    'config',
    'routes',
    'resources',
    'storage/app',
    'storage/logs',
];

// Directories to skip
$skipDirs = [
    'vendor',
    'node_modules',
    'storage/framework',
    'bootstrap/cache',
    '.git',
];

echo "[1/5] SCANNING FOR MALWARE SIGNATURES\n";
echo str_repeat("-", 80) . "\n";

foreach ($dirsToScan as $dir) {
    $fullPath = BASE_PATH . '/' . $dir;
    if (!is_dir($fullPath)) continue;
    
    echo "Scanning: $dir/\n";
    scanDirectory($fullPath, $dir);
}

echo "\n";

// File integrity check
echo "[2/5] CHECKING FILE INTEGRITY\n";
echo str_repeat("-", 80) . "\n";

checkFileIntegrity();

echo "\n";

// Permission check
echo "[3/5] CHECKING FILE PERMISSIONS\n";
echo str_repeat("-", 80) . "\n";

checkPermissions();

echo "\n";

// Check for suspicious files
echo "[4/5] CHECKING FOR SUSPICIOUS FILES\n";
echo str_repeat("-", 80) . "\n";

checkSuspiciousFiles();

echo "\n";

// Summary
echo "[5/5] SCAN SUMMARY\n";
echo str_repeat("-", 80) . "\n";

echo "Files scanned: $scannedFiles\n";
echo "Threats found: " . count($threats) . "\n";
echo "Suspicious patterns: " . count($suspicious) . "\n\n";

if (count($threats) > 0) {
    echo RED . "⚠️  CRITICAL THREATS DETECTED!\n" . NC;
    echo "\nThreats:\n";
    foreach ($threats as $threat) {
        echo RED . "  ❌ {$threat['file']}\n" . NC;
        echo "     Line {$threat['line']}: {$threat['pattern']}\n";
        echo "     Reason: {$threat['reason']}\n\n";
    }
}

if (count($suspicious) > 0) {
    echo YELLOW . "⚠️  SUSPICIOUS PATTERNS FOUND\n" . NC;
    echo "\nSuspicious:\n";
    foreach (array_slice($suspicious, 0, 20) as $item) {
        echo YELLOW . "  ⚠️  {$item['file']}\n" . NC;
        echo "     Line {$item['line']}: {$item['pattern']}\n";
        echo "     Context: {$item['reason']}\n\n";
    }
    
    if (count($suspicious) > 20) {
        echo "... and " . (count($suspicious) - 20) . " more suspicious patterns\n\n";
    }
}

if (count($threats) === 0 && count($suspicious) === 0) {
    echo GREEN . "✅ No threats detected\n" . NC;
}

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "RECOMMENDATIONS:\n";
echo str_repeat("=", 80) . "\n";

if (count($threats) > 0) {
    echo "1. IMMEDIATELY remove or quarantine detected threat files\n";
    echo "2. Review access logs: /var/log/apache2/access.log\n";
    echo "3. Change ALL passwords (database, SSH, admin users)\n";
    echo "4. Review user accounts for unauthorized access\n";
    echo "5. Restore from clean backup if available\n";
}

echo "6. Install fail2ban: sudo apt install fail2ban\n";
echo "7. Enable ModSecurity (Web Application Firewall)\n";
echo "8. Set up file integrity monitoring (AIDE)\n";
echo "9. Disable dangerous PHP functions in php.ini\n";
echo "10. Keep all software updated\n\n";

// Functions

function scanDirectory($path, $relativePath) {
    global $malwareSignatures, $threats, $suspicious, $scannedFiles, $skipDirs;
    
    $items = scandir($path);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $path . '/' . $item;
        $relPath = $relativePath . '/' . $item;
        
        // Skip directories
        if (is_dir($fullPath)) {
            $skip = false;
            foreach ($skipDirs as $skipDir) {
                if (strpos($relPath, $skipDir) !== false) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip) {
                scanDirectory($fullPath, $relPath);
            }
            continue;
        }
        
        // Only scan PHP files
        if (!preg_match('/\.php$/i', $item)) continue;
        
        $scannedFiles++;
        scanFile($fullPath, $relPath);
    }
}

function scanFile($filePath, $relativePath) {
    global $malwareSignatures, $threats, $suspicious;
    
    $content = @file_get_contents($filePath);
    if ($content === false) return;
    
    $lines = explode("\n", $content);
    
    foreach ($malwareSignatures as $pattern => $reason) {
        $isRegex = @preg_match('/' . $pattern . '/', '') !== false;
        
        foreach ($lines as $lineNum => $line) {
            $found = false;
            
            if ($isRegex) {
                if (preg_match('/' . $pattern . '/i', $line)) {
                    $found = true;
                }
            } else {
                if (stripos($line, $pattern) !== false) {
                    $found = true;
                }
            }
            
            if ($found) {
                $isCritical = in_array($pattern, ['wp-blog-header', 'wp-cron', 'wp-login', 'c99shell', 'r57shell', 'eval(', 'base64_decode']);
                
                $entry = [
                    'file' => $relativePath,
                    'line' => $lineNum + 1,
                    'pattern' => substr($line, 0, 100),
                    'reason' => $reason,
                ];
                
                if ($isCritical) {
                    $threats[] = $entry;
                } else {
                    $suspicious[] = $entry;
                }
            }
        }
    }
}

function checkFileIntegrity() {
    // Check if public/index.php is correct Laravel file
    $indexPath = BASE_PATH . '/public/index.php';
    
    if (!file_exists($indexPath)) {
        echo RED . "❌ public/index.php is missing!\n" . NC;
        return;
    }
    
    $content = file_get_contents($indexPath);
    
    if (strpos($content, 'Illuminate\Contracts\Http\Kernel') !== false && 
        strpos($content, 'wp-blog-header') === false) {
        echo GREEN . "✅ public/index.php is clean Laravel file\n" . NC;
    } else {
        echo RED . "❌ public/index.php may be compromised\n" . NC;
    }
    
    // Check .env exists
    if (file_exists(BASE_PATH . '/.env')) {
        echo GREEN . "✅ .env file exists\n" . NC;
    } else {
        echo RED . "❌ .env file missing\n" . NC;
    }
}

function checkPermissions() {
    $paths = [
        'storage' => 0775,
        'bootstrap/cache' => 0775,
        'public' => 0755,
    ];
    
    foreach ($paths as $path => $expectedPerms) {
        $fullPath = BASE_PATH . '/' . $path;
        
        if (!file_exists($fullPath)) {
            echo YELLOW . "⚠️  $path does not exist\n" . NC;
            continue;
        }
        
        $perms = fileperms($fullPath) & 0777;
        
        if ($perms == $expectedPerms) {
            echo GREEN . "✅ $path permissions correct ($expectedPerms)\n" . NC;
        } else {
            $actualPerms = decoct($perms);
            echo YELLOW . "⚠️  $path permissions: $actualPerms (expected: $expectedPerms)\n" . NC;
        }
    }
}

function checkSuspiciousFiles() {
    global $threats;
    
    // Check for files with suspicious names
    $suspiciousNames = [
        '*.exe',
        '*.sh',
        '*.bat',
        'shell*.php',
        'backdoor*.php',
        'hack*.php',
        'exploit*.php',
        'c99*.php',
        'r57*.php',
        'webshell*.php',
        'wp-*.php', // WordPress files shouldn't be in Laravel
    ];
    
    foreach ($suspiciousNames as $pattern) {
        $matches = glob(BASE_PATH . '/public/' . $pattern);
        
        foreach ($matches as $match) {
            $relativePath = str_replace(BASE_PATH . '/', '', $match);
            echo RED . "❌ Suspicious file: $relativePath\n" . NC;
            
            $threats[] = [
                'file' => $relativePath,
                'line' => 0,
                'pattern' => 'Suspicious filename',
                'reason' => 'File name matches malware pattern',
            ];
        }
    }
    
    // Check for hidden PHP files
    $hiddenFiles = glob(BASE_PATH . '/public/.*.php', GLOB_BRACE);
    
    foreach ($hiddenFiles as $file) {
        $relativePath = str_replace(BASE_PATH . '/', '', $file);
        echo RED . "❌ Hidden PHP file: $relativePath\n" . NC;
        
        $threats[] = [
            'file' => $relativePath,
            'line' => 0,
            'pattern' => 'Hidden file',
            'reason' => 'Hidden PHP files are suspicious',
        ];
    }
    
    if (count($threats) === 0) {
        echo GREEN . "✅ No suspicious files found\n" . NC;
    }
}
?>
