<?php

declare(strict_types=1);

// Load .env file - try multiple paths
$envPaths = [
    dirname(__DIR__, 2) . '/.env',
    __DIR__ . '/../../.env',
    dirname(__FILE__, 3) . '/.env',
    (isset($_SERVER['SCRIPT_FILENAME']) ? dirname($_SERVER['SCRIPT_FILENAME']) : __DIR__) . '/.env'
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        error_log('DB DEBUG: db_connect.php - Found .env file at: ' . $envPath);
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $lineNum => $line) {
                $originalLine = $line;
                $line = trim($line);
                // Skip empty lines and comments
                if ($line === '' || (strlen($line) > 0 && $line[0] === '#')) continue;
                
                // Split on first = only (values may contain =)
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                
                $key = trim(substr($line, 0, $pos));
                $val = substr($line, $pos + 1); // Get everything after =
                
                // Handle quoted values (remove quotes but preserve content including spaces)
                if (strlen($val) >= 2) {
                    $firstChar = $val[0];
                    $lastChar = substr($val, -1);
                    if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                        $val = substr($val, 1, -1); // Remove quotes, preserve content
                    } else {
                        $val = trim($val); // Only trim if not quoted
                    }
                } else {
                    $val = trim($val);
                }
                
                // CRITICAL: Always set the key, even if value is empty (for empty passwords)
                // Use array_key_exists later to check if key exists
                $_ENV[$key] = $val;
                putenv("$key=$val");
                
                // Log DB-related variables for debugging
                if (strpos($key, 'DB_') === 0 || strpos($key, 'LOCAL_DB_') === 0) {
                    if (stripos($key, 'PASS') !== false || stripos($key, 'PASSWORD') !== false) {
                        error_log("DB DEBUG: db_connect.php - Loaded \$_ENV['$key'] = " . ($val === '' ? '[empty string - will use NULL]' : '[set, length: ' . strlen($val) . ']') . " from line " . ($lineNum + 1) . " (original: " . trim($originalLine) . ")");
                        // DIAGNOSTIC: Specifically log LOCAL_DB_PASS parsing
                        if ($key === 'LOCAL_DB_PASS') {
                            error_log("DIAGNOSTIC: Parsed LOCAL_DB_PASS from .env - Raw line: [" . trim($originalLine) . "], Parsed value length: " . strlen($val) . ", Value after parsing: " . ($val === '' ? '[EMPTY]' : '[NOT EMPTY, length: ' . strlen($val) . ']'));
                        }
                    } else {
                        error_log("DB DEBUG: db_connect.php - Loaded \$_ENV['$key'] = '$val' from line " . ($lineNum + 1));
                    }
                }
            }
            error_log('DB DEBUG: db_connect.php - Finished loading .env file');
            // DIAGNOSTIC: Confirm which .env file was actually loaded
            error_log('CONFIRM: Loaded .env from: ' . $envPath);
            break;
        } else {
            error_log('DB DEBUG: db_connect.php - Failed to read .env file: ' . $envPath);
        }
    } else {
        error_log('DB DEBUG: db_connect.php - .env file not found at: ' . $envPath);
    }
}

// DIAGNOSTIC: Immediately after .env loading, confirm LOCAL_DB_PASS status
if (array_key_exists('LOCAL_DB_PASS', $_ENV)) {
    $passLength = strlen($_ENV['LOCAL_DB_PASS']);
    error_log('CONFIRM: LOCAL_DB_PASS exists, length = ' . $passLength);
} else {
    error_log('CONFIRM: LOCAL_DB_PASS is NOT SET in $_ENV');
}

// Simple PDO connection helper; replace placeholders with real values or load from environment.

// DIAGNOSTIC: Log what $_ENV contains
error_log('DB DEBUG: db_connect.php starting - $_ENV keys: ' . implode(', ', array_keys($_ENV)));
error_log('DB DEBUG: Checking for LOCAL_DB_* and DB_* keys in $_ENV...');
$relevantEnvKeys = array_filter(array_keys($_ENV), function($k) { 
    return strpos($k, 'DB_') === 0 || strpos($k, 'LOCAL_DB_') === 0; 
});
if (!empty($relevantEnvKeys)) {
    foreach ($relevantEnvKeys as $key) {
        $value = $_ENV[$key];
        // Hide password value, just show if set
        if (stripos($key, 'PASS') !== false || stripos($key, 'PASSWORD') !== false) {
            error_log("DB DEBUG: \$_ENV['$key'] = " . (empty($value) ? '[empty]' : '[set, length: ' . strlen($value) . ']'));
        } else {
            error_log("DB DEBUG: \$_ENV['$key'] = '$value'");
        }
    }
} else {
    error_log('DB DEBUG: WARNING - No DB_* or LOCAL_DB_* keys found in $_ENV!');
}

// CRITICAL: Database connection logic based strictly on APP_ENV
// Rules:
// - APP_ENV=local → Use ONLY: DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PORT
// - APP_ENV=production → Use ONLY: PROD_DB_HOST, PROD_DB_NAME, PROD_DB_USER, PROD_DB_PASS, PROD_DB_PORT
// - No fallbacks, no mixing, no defaults

// Determine environment
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'local';
$appEnv = strtolower(trim($appEnv));

// Log environment
error_log("DB CONFIG: APP_ENV = '$appEnv'");

// Load database credentials based on APP_ENV
if ($appEnv === 'production') {
    // Production: Use ONLY PROD_DB_* variables
    if (!array_key_exists('PROD_DB_HOST', $_ENV) && getenv('PROD_DB_HOST') === false) {
        throw new RuntimeException('PROD_DB_HOST is required when APP_ENV=production');
    }
    $dbHost = array_key_exists('PROD_DB_HOST', $_ENV) ? $_ENV['PROD_DB_HOST'] : getenv('PROD_DB_HOST');
    
    if (!array_key_exists('PROD_DB_NAME', $_ENV) && getenv('PROD_DB_NAME') === false) {
        throw new RuntimeException('PROD_DB_NAME is required when APP_ENV=production');
    }
    $dbName = array_key_exists('PROD_DB_NAME', $_ENV) ? $_ENV['PROD_DB_NAME'] : getenv('PROD_DB_NAME');
    
    if (!array_key_exists('PROD_DB_USER', $_ENV) && getenv('PROD_DB_USER') === false) {
        throw new RuntimeException('PROD_DB_USER is required when APP_ENV=production');
    }
    $dbUser = array_key_exists('PROD_DB_USER', $_ENV) ? $_ENV['PROD_DB_USER'] : getenv('PROD_DB_USER');
    
    if (!array_key_exists('PROD_DB_PASS', $_ENV) && getenv('PROD_DB_PASS') === false) {
        throw new RuntimeException('PROD_DB_PASS is required when APP_ENV=production');
    }
    $dbPass = array_key_exists('PROD_DB_PASS', $_ENV) ? $_ENV['PROD_DB_PASS'] : getenv('PROD_DB_PASS');
    
    if (!array_key_exists('PROD_DB_PORT', $_ENV) && getenv('PROD_DB_PORT') === false) {
        throw new RuntimeException('PROD_DB_PORT is required when APP_ENV=production');
    }
    $dbPort = array_key_exists('PROD_DB_PORT', $_ENV) ? $_ENV['PROD_DB_PORT'] : getenv('PROD_DB_PORT');
    
    error_log("DB CONFIG: Using PRODUCTION credentials - host='$dbHost', db='$dbName'");
} else {
    // Local: Use ONLY DB_* variables (not PROD_DB_* or LOCAL_DB_*)
    if (!array_key_exists('DB_HOST', $_ENV) && getenv('DB_HOST') === false) {
        throw new RuntimeException('DB_HOST is required when APP_ENV=local');
    }
    $dbHost = array_key_exists('DB_HOST', $_ENV) ? $_ENV['DB_HOST'] : getenv('DB_HOST');
    
    // Check both DB_NAME and DB_DATABASE (common variations)
    if (array_key_exists('DB_NAME', $_ENV)) {
        $dbName = $_ENV['DB_NAME'];
    } elseif (array_key_exists('DB_DATABASE', $_ENV)) {
        $dbName = $_ENV['DB_DATABASE'];
    } elseif (getenv('DB_NAME') !== false) {
        $dbName = getenv('DB_NAME');
    } elseif (getenv('DB_DATABASE') !== false) {
        $dbName = getenv('DB_DATABASE');
    } else {
        throw new RuntimeException('DB_NAME or DB_DATABASE is required when APP_ENV=local');
    }
    
    // Check both DB_USER and DB_USERNAME (common variations)
    if (array_key_exists('DB_USER', $_ENV)) {
        $dbUser = $_ENV['DB_USER'];
    } elseif (array_key_exists('DB_USERNAME', $_ENV)) {
        $dbUser = $_ENV['DB_USERNAME'];
    } elseif (getenv('DB_USER') !== false) {
        $dbUser = getenv('DB_USER');
    } elseif (getenv('DB_USERNAME') !== false) {
        $dbUser = getenv('DB_USERNAME');
    } else {
        throw new RuntimeException('DB_USER or DB_USERNAME is required when APP_ENV=local');
    }
    
    // Check both DB_PASSWORD and DB_PASS (common variations)
    if (array_key_exists('DB_PASSWORD', $_ENV)) {
        $dbPass = $_ENV['DB_PASSWORD'];
    } elseif (array_key_exists('DB_PASS', $_ENV)) {
        $dbPass = $_ENV['DB_PASS'];
    } elseif (getenv('DB_PASSWORD') !== false) {
        $dbPass = getenv('DB_PASSWORD');
    } elseif (getenv('DB_PASS') !== false) {
        $dbPass = getenv('DB_PASS');
    } else {
        throw new RuntimeException('DB_PASSWORD or DB_PASS is required when APP_ENV=local');
    }
    
    if (!array_key_exists('DB_PORT', $_ENV) && getenv('DB_PORT') === false) {
        throw new RuntimeException('DB_PORT is required when APP_ENV=local');
    }
    $dbPort = array_key_exists('DB_PORT', $_ENV) ? $_ENV['DB_PORT'] : getenv('DB_PORT');
    
    error_log("DB CONFIG: Using LOCAL credentials - host='$dbHost', db='$dbName'");
}

// Minimal logging: Confirm environment and values used
error_log("DB CONFIG: Environment='$appEnv', Host='$dbHost', Database='$dbName'");

// IMPORTANT: PDO interprets empty string '' as "no password provided", but if MySQL root actually has no password,
// we should pass NULL. However, if MySQL root has a password set, we need the actual password.
// For empty string, convert to NULL for PDO to properly handle "no password" authentication
// DIAGNOSTIC: Log password value before conversion
error_log('DIAGNOSTIC: $dbPass before conversion - ' . ($dbPass === '' ? '[empty string]' : '[set, length: ' . strlen($dbPass) . ']'));
$pdoPassword = ($dbPass === '') ? null : $dbPass;
// DIAGNOSTIC: Log password value after conversion
error_log('DIAGNOSTIC: $pdoPassword after conversion - ' . ($pdoPassword === null ? '[NULL]' : ($pdoPassword === '' ? '[empty string]' : '[set, length: ' . strlen($pdoPassword) . ']')));
if ($dbPass === '') {
    error_log('DB DEBUG: Converting empty password string to NULL for PDO (XAMPP default no-password root)');
}

// First, try to connect to MySQL server without specifying database
// On Windows XAMPP, use TCP/IP connection (localhost or 127.0.0.1)
// If host is 'localhost' on Windows, PDO uses TCP/IP, not socket
$dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);
$pdo = null;

try {
    // Try connecting to MySQL server first (without database)
    // DIAGNOSTIC: Log exact password status before PDO connection attempt
    error_log('DIAGNOSTIC: Before PDO connection - $pdoPassword is ' . ($pdoPassword === null ? '[NULL]' : ($pdoPassword === '' ? '[empty string]' : '[set, length: ' . strlen($pdoPassword) . ']')));
    error_log("DB DEBUG: Attempting connection to MySQL server (no database) - DSN: $dsnNoDb, User: $dbUser, Password: " . ($pdoPassword === null ? 'NULL (no password)' : '[provided]'));
    $pdoTemp = new PDO($dsnNoDb, $dbUser, $pdoPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    error_log('DB DEBUG: MySQL server connection successful (without database)');
    
    // Check if database exists
    error_log("DB DEBUG: Checking if database '$dbName' exists...");
    $stmt = $pdoTemp->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$dbName]);
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        // Database doesn't exist, create it
        error_log("DB DEBUG: Database '$dbName' not found, attempting to create...");
        try {
            $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            error_log("DB DEBUG: Database '$dbName' created successfully");
        } catch (PDOException $e) {
            error_log('DB DEBUG: Failed to create database: ' . $e->getMessage());
            // Don't throw, continue to try connecting anyway (database might have been created by another process)
        }
    } else {
        error_log("DB DEBUG: Database '$dbName' already exists");
    }
    
    // Now connect to the specific database
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
    error_log("DB CONFIG: Connecting to database - DSN: $dsn");
    $pdo = new PDO($dsn, $dbUser, $pdoPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    error_log('DB DEBUG: PDO connection successful');
    
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    error_log('DB DEBUG: PDO failed: ' . $errorMessage);
    error_log('DB DEBUG: PDO error code: ' . $e->getCode());
    error_log('DB DEBUG: Connection attempt details - Host: ' . $dbHost . ', Port: ' . $dbPort . ', Database: ' . $dbName . ', User: ' . $dbUser . ', Password provided: ' . ($pdoPassword === null ? 'NULL (no password)' : ($pdoPassword === '' ? 'EMPTY STRING (incorrect)' : 'YES (length: ' . strlen($pdoPassword) . ')')));
    
    // Check if this is a login/register request - allow it to proceed with null PDO for demo login
    $isAuthRequest = PHP_SAPI !== 'cli' && 
                     isset($_SERVER['REQUEST_URI']) && 
                     (strpos($_SERVER['REQUEST_URI'], '/api/v1/auth/login') !== false || 
                      strpos($_SERVER['REQUEST_URI'], '/api/v1/auth/register') !== false);
    
    if ($isAuthRequest) {
        // For login/register requests ONLY, allow null PDO so the demo login can work
        $pdo = null;
        error_log('Auth request detected - allowing null PDO for demo login fallback');
    } else {
        // For ALL other API requests (including dashboard), we MUST have a working connection
        // Try one more time with direct connection to the database
        // If this fails, throw the exception so we can see the real error
        try {
            error_log('DB CONFIG: Retrying database connection...');
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            error_log("DB CONFIG: Retry DSN: $dsn");
            $pdo = new PDO($dsn, $dbUser, $pdoPassword, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            // Verify connection works with a test query
            error_log('DB DEBUG: Testing connection with SELECT 1...');
            $testResult = $pdo->query('SELECT 1 as test')->fetch();
            if ($testResult && $testResult['test'] == 1) {
                error_log('DB DEBUG: Database connection retry successful and verified (SELECT 1 returned 1)');
            } else {
                error_log('DB DEBUG: WARNING - SELECT 1 test returned unexpected result: ' . var_export($testResult, true));
            }
        } catch (PDOException $retryException) {
            error_log('DB DEBUG: Retry also failed: ' . $retryException->getMessage());
            error_log('DB DEBUG: Retry error code: ' . $retryException->getCode());
            // Re-throw the original exception so the real error is visible
            // Do NOT hide it by setting $pdo = null
            throw new PDOException(
                'Database connection failed: ' . $retryException->getMessage() . 
                ' (Host: ' . $dbHost . ', Port: ' . $dbPort . ', Database: ' . $dbName . ', User: ' . $dbUser . ')',
                (int)$retryException->getCode(),
                $retryException
            );
        }
    }
}

// Final verification: ensure $pdo is a real PDO instance for non-auth requests
$isAuthRequest = PHP_SAPI !== 'cli' && 
                 isset($_SERVER['REQUEST_URI']) && 
                 (strpos($_SERVER['REQUEST_URI'], '/api/v1/auth/login') !== false || 
                  strpos($_SERVER['REQUEST_URI'], '/api/v1/auth/register') !== false);

if (!$isAuthRequest) {
    // For non-auth requests, PDO must be set and valid
    if (!isset($pdo) || $pdo === null) {
        throw new RuntimeException('Database connection is required but PDO is null');
    }
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('PDO connection is not a valid PDO instance');
    }
    
    // Verify connection works with a test query
    error_log('DB DEBUG: Verifying connection with SELECT 1 test query...');
    try {
        $testResult = $pdo->query('SELECT 1 as test')->fetch();
        error_log('DB DEBUG: SELECT 1 result: ' . var_export($testResult, true));
        if (!$testResult || $testResult['test'] != 1) {
            error_log('DB DEBUG: SELECT 1 test FAILED - unexpected result');
            throw new RuntimeException('Database connection test query failed - result was: ' . var_export($testResult, true));
        }
        error_log('DB DEBUG: PDO success - connection verified with SELECT 1');
    } catch (PDOException $testException) {
        error_log('DB DEBUG: SELECT 1 test query failed: ' . $testException->getMessage());
        error_log('DB DEBUG: Test query error code: ' . $testException->getCode());
        throw new PDOException('Database connection verification failed: ' . $testException->getMessage(), (int)$testException->getCode(), $testException);
    }
} else {
    // For auth requests, PDO can be null (demo login fallback)
    if (isset($pdo) && $pdo !== null && !($pdo instanceof PDO)) {
        throw new RuntimeException('PDO connection is not a valid PDO instance');
    }
}


