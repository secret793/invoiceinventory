<?php
/**
 * Simple PHP Script to Fix Receipts Table ID Column
 * Run from command line: php simple_fix_receipts.php
 */

// Get database config from .env file
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found!\n");
}

$envContent = file_get_contents($envFile);
preg_match('/DB_HOST=(.*)/', $envContent, $hostMatch);
preg_match('/DB_DATABASE=(.*)/', $envContent, $dbMatch);
preg_match('/DB_USERNAME=(.*)/', $envContent, $userMatch);
preg_match('/DB_PASSWORD=(.*)/', $envContent, $passMatch);

$host = trim($hostMatch[1] ?? 'localhost');
$dbname = trim($dbMatch[1] ?? '');
$username = trim($userMatch[1] ?? 'root');
$password = trim($passMatch[1] ?? '');

echo "========================================\n";
echo "  Fix Receipts Table ID Column\n";
echo "========================================\n\n";

if (empty($dbname)) {
    die("ERROR: Database name not found in .env file!\n");
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database: $dbname\n\n";
    
    // Check current column structure
    echo "Checking 'receipts.id' column...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        die("ERROR: 'receipts' table or 'id' column not found!\n");
    }
    
    echo "Current: " . $column['Extra'] . "\n\n";
    
    if (stripos($column['Extra'], 'auto_increment') === false) {
        echo "⚠  ID column missing AUTO_INCREMENT!\n";
        echo "Applying fix...\n\n";
        
        // Fix the column
        $sql = "ALTER TABLE `receipts` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $pdo->exec($sql);
        
        echo "✓ Fixed successfully!\n\n";
        
        // Verify
        $stmt = $pdo->query("SHOW COLUMNS FROM receipts WHERE Field = 'id'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Updated: " . $column['Extra'] . "\n\n";
        
        echo "✓✓ Receipt generation should work now!\n";
    } else {
        echo "✓ ID column already has AUTO_INCREMENT\n";
        echo "No fix needed!\n";
    }
    
} catch (PDOException $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
