<?php
/**
 * Production Diagnostic Script
 * Upload this to /var/www/html/safety_campaign_alertaraqc/diagnostic_check.php
 * Access via: https://campaign.alertaraqc.com/diagnostic_check.php
 */

header('Content-Type: text/plain');
echo "=== PRODUCTION DIAGNOSTIC CHECK ===\n\n";

// 1. Check .env file
echo "1. CHECKING .ENV FILE:\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "   ✓ .env file exists at: $envPath\n";
    $envContent = file_get_contents($envPath);
    
    // Check for required variables
    $required = ['APP_ENV', 'PROD_DB_HOST', 'PROD_DB_NAME', 'PROD_DB_USER', 'PROD_DB_PASS'];
    foreach ($required as $var) {
        if (strpos($envContent, $var) !== false) {
            echo "   ✓ $var is present\n";
        } else {
            echo "   ✗ $var is MISSING\n";
        }
    }
    
    // Check APP_ENV value
    if (preg_match('/APP_ENV\s*=\s*(\w+)/', $envContent, $matches)) {
        echo "   → APP_ENV = " . $matches[1] . "\n";
    }
} else {
    echo "   ✗ .env file NOT FOUND at: $envPath\n";
}

echo "\n2. CHECKING PHP ENVIRONMENT:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "   Script Filename: " . __FILE__ . "\n";
echo "   HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "   Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";

echo "\n3. CHECKING DATABASE CONNECTION:\n";
try {
    require_once __DIR__ . '/src/Config/db_connect.php';
    
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "   ✓ PDO connection established\n";
        
        // Test query
        $result = $pdo->query("SELECT DATABASE() as db, VERSION() as version")->fetch();
        echo "   ✓ Connected to database: " . $result['db'] . "\n";
        echo "   ✓ MySQL version: " . $result['version'] . "\n";
        
        // Check if tables exist
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "   ✓ Found " . count($tables) . " tables\n";
        
        // Check critical tables
        $criticalTables = ['users', 'campaigns', 'notifications'];
        foreach ($criticalTables as $table) {
            if (in_array($table, $tables)) {
                echo "   ✓ Table '$table' exists\n";
            } else {
                echo "   ✗ Table '$table' MISSING\n";
            }
        }
    } else {
        echo "   ✗ PDO connection FAILED - \$pdo is not set or not a PDO instance\n";
    }
} catch (Exception $e) {
    echo "   ✗ DATABASE ERROR: " . $e->getMessage() . "\n";
    echo "   Error Code: " . $e->getCode() . "\n";
}

echo "\n4. CHECKING FILE PERMISSIONS:\n";
$checkFiles = [
    '.env' => $envPath,
    'index.php' => __DIR__ . '/index.php',
    'db_connect.php' => __DIR__ . '/src/Config/db_connect.php'
];

foreach ($checkFiles as $name => $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path))['name'] : 'unknown';
        echo "   $name: $perms (owner: $owner)\n";
    } else {
        echo "   $name: NOT FOUND\n";
    }
}

echo "\n5. CHECKING PATH CONFIGURATION:\n";
require_once __DIR__ . '/header/includes/path_helper.php';
echo "   basePath: '$basePath'\n";
echo "   apiPath: '$apiPath'\n";
echo "   publicPath: '$publicPath'\n";

echo "\n6. CHECKING LOADED ENVIRONMENT VARIABLES:\n";
$envVars = ['APP_ENV', 'DB_HOST', 'PROD_DB_HOST', 'GOOGLE_CLIENT_ID', 'OPENAI_API_KEY'];
foreach ($envVars as $var) {
    if (isset($_ENV[$var])) {
        $value = $_ENV[$var];
        // Hide sensitive values
        if (strpos($var, 'PASS') !== false || strpos($var, 'SECRET') !== false || strpos($var, 'KEY') !== false) {
            echo "   $var: [SET, length=" . strlen($value) . "]\n";
        } else {
            echo "   $var: $value\n";
        }
    } else {
        echo "   $var: NOT SET\n";
    }
}

echo "\n7. TESTING API ENDPOINT:\n";
$testUrl = 'http://localhost/index.php/api/v1/notifications';
echo "   Testing: $testUrl\n";

// Simulate API request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/index.php/api/v1/notifications';

echo "\n=== END OF DIAGNOSTIC ===\n";
echo "\nIf you see errors above, please share this output.\n";
