<?php
/**
 * Test endpoint to debug auth_otp.php issues
 */

// Suppress all errors to output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$tests = [];

// Test 1: Check if db_connect.php exists
$dbConnectPath = __DIR__ . '/../src/Config/db_connect.php';
$tests['db_connect_exists'] = file_exists($dbConnectPath);

// Test 2: Check if vendor autoload exists
$vendorPath = __DIR__ . '/../vendor/autoload.php';
$tests['vendor_autoload_exists'] = file_exists($vendorPath);

// Test 3: Try to load database connection
try {
    require_once $dbConnectPath;
    $tests['db_connection'] = isset($pdo) ? 'success' : 'pdo_not_set';
} catch (Exception $e) {
    $tests['db_connection'] = 'error: ' . $e->getMessage();
}

// Test 4: Check if campaign_users table exists
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_users'");
        $tests['campaign_users_table'] = $stmt->rowCount() > 0 ? 'exists' : 'not_found';
    } catch (Exception $e) {
        $tests['campaign_users_table'] = 'error: ' . $e->getMessage();
    }
}

// Test 5: Check if campaign_otp_codes table exists
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_otp_codes'");
        $tests['campaign_otp_codes_table'] = $stmt->rowCount() > 0 ? 'exists' : 'not_found';
    } catch (Exception $e) {
        $tests['campaign_otp_codes_table'] = 'error: ' . $e->getMessage();
    }
}

// Test 6: Check if JWT library exists
if (file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        $tests['jwt_class'] = class_exists('Firebase\JWT\JWT') ? 'exists' : 'not_found';
    } catch (Exception $e) {
        $tests['jwt_class'] = 'error: ' . $e->getMessage();
    }
}

// Test 7: Check if MailService exists
$tests['mail_service_class'] = class_exists('App\Services\MailService') ? 'exists' : 'not_found';

// Test 8: Try to query admin user
if (isset($pdo) && $tests['campaign_users_table'] === 'exists') {
    try {
        $stmt = $pdo->prepare("SELECT id, email, fullname FROM campaign_users WHERE email = ?");
        $stmt->execute(['admin@alertaraqc.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $tests['admin_user'] = $user ? 'found: ' . $user['fullname'] : 'not_found';
    } catch (Exception $e) {
        $tests['admin_user'] = 'error: ' . $e->getMessage();
    }
}

echo json_encode([
    'status' => 'ok',
    'php_version' => PHP_VERSION,
    'tests' => $tests
], JSON_PRETTY_PRINT);
