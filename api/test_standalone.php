<?php
/**
 * Test standalone database connection
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Standalone DB Connection Test</h1>";
echo "<pre>";

// Load .env file manually
$envPath = dirname(__DIR__) . '/.env';

// Check if running in production
$isProduction = isset($_SERVER['SERVER_NAME']) && 
                (strpos($_SERVER['SERVER_NAME'], 'alertaraqc.com') !== false ||
                 strpos($_SERVER['SERVER_NAME'], '72.60.209.226') !== false);

echo "Environment: " . ($isProduction ? 'PRODUCTION' : 'LOCAL') . "\n";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n\n";

// Default values
$dbHost = 'localhost';
$dbName = 'LGU';
$dbUser = 'root';
$dbPass = '';
$dbPort = '3306';

echo ".env file path: $envPath\n";
echo ".env exists: " . (file_exists($envPath) ? 'YES' : 'NO') . "\n\n";

if (file_exists($envPath)) {
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines) {
        echo "Found " . count($lines) . " lines in .env\n\n";
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;
            
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            
            // Remove quotes
            if (strlen($val) > 1 && 
                (($val[0] === '"' && substr($val, -1) === '"') || 
                 ($val[0] === "'" && substr($val, -1) === "'"))) {
                $val = substr($val, 1, -1);
            }
            
            // Show DB-related vars
            if (strpos($key, 'DB_') !== false) {
                if (strpos($key, 'PASS') !== false) {
                    echo "$key = [" . strlen($val) . " chars]\n";
                } else {
                    echo "$key = $val\n";
                }
            }
            
            // Load credentials
            if ($isProduction) {
                if ($key === 'PROD_DB_HOST') $dbHost = $val;
                if ($key === 'PROD_DB_NAME') $dbName = $val;
                if ($key === 'PROD_DB_USER') $dbUser = $val;
                if ($key === 'PROD_DB_PASS') $dbPass = $val;
                if ($key === 'PROD_DB_PORT') $dbPort = $val;
            } else {
                if ($key === 'DB_HOST') $dbHost = $val;
                if ($key === 'DB_NAME') $dbName = $val;
                if ($key === 'DB_USER') $dbUser = $val;
                if ($key === 'DB_PASSWORD') $dbPass = $val;
                if ($key === 'DB_PORT') $dbPort = $val;
            }
        }
    }
}

echo "\n=== Using Credentials ===\n";
echo "Host: $dbHost\n";
echo "Port: $dbPort\n";
echo "Database: $dbName\n";
echo "User: $dbUser\n";
echo "Password: " . (empty($dbPass) ? '[EMPTY]' : '[' . strlen($dbPass) . ' chars]') . "\n\n";

echo "=== Testing Connection ===\n";
try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    echo "DSN: $dsn\n";
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ Connection successful!\n\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM campaign_users");
    $result = $stmt->fetch();
    echo "✓ campaign_users table exists\n";
    echo "  User count: " . $result['count'] . "\n\n";
    
    // Test admin user
    $stmt = $pdo->prepare("SELECT id, email, fullname FROM campaign_users WHERE email = ?");
    $stmt->execute(['admin@alertaraqc.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Admin user found:\n";
        echo "  ID: " . $user['id'] . "\n";
        echo "  Email: " . $user['email'] . "\n";
        echo "  Name: " . $user['fullname'] . "\n";
    } else {
        echo "✗ Admin user NOT found\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "\n=== Testing vendor/autoload.php ===\n";
$vendorPath = __DIR__ . '/../vendor/autoload.php';
echo "Path: $vendorPath\n";
echo "Exists: " . (file_exists($vendorPath) ? 'YES' : 'NO') . "\n";

if (file_exists($vendorPath)) {
    require_once $vendorPath;
    echo "✓ Loaded successfully\n";
    echo "JWT class exists: " . (class_exists('Firebase\JWT\JWT') ? 'YES' : 'NO') . "\n";
}

echo "</pre>";
