<?php
/**
 * Production BasePath Diagnostic Script
 * Upload to production root and access: https://campaign.alertaraqc.com/check_production_basepath.php
 * This will show exactly what the server is detecting
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PRODUCTION BASEPATH DIAGNOSTIC ===\n\n";

// Show all relevant server variables
echo "SERVER VARIABLES:\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "\n\n";

// Test production detection logic
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$serverName = strtolower($_SERVER['SERVER_NAME'] ?? '');
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

echo "NORMALIZED VALUES:\n";
echo "host (lowercase): '$host'\n";
echo "serverName (lowercase): '$serverName'\n";
echo "requestUri: '$requestUri'\n\n";

// Test each detection method
echo "PRODUCTION DETECTION TESTS:\n";
$test1 = strpos($host, 'alertaraqc.com') !== false;
echo "  strpos(host, 'alertaraqc.com'): " . ($test1 ? 'TRUE' : 'FALSE') . "\n";

$test2 = $host === 'campaign.alertaraqc.com';
echo "  host === 'campaign.alertaraqc.com': " . ($test2 ? 'TRUE' : 'FALSE') . "\n";

$test3 = strpos($serverName, 'alertaraqc.com') !== false;
echo "  strpos(serverName, 'alertaraqc.com'): " . ($test3 ? 'TRUE' : 'FALSE') . "\n";

$test4 = strpos($requestUri, 'alertaraqc.com') !== false;
echo "  strpos(requestUri, 'alertaraqc.com'): " . ($test4 ? 'TRUE' : 'FALSE') . "\n";

$test5 = (strpos($host, 'localhost') === false && 
          strpos($serverName, 'localhost') === false &&
          $host !== '127.0.0.1' && 
          $serverName !== '127.0.0.1' &&
          $host !== '' &&
          strpos($host, '.local') === false);
echo "  NOT localhost check: " . ($test5 ? 'TRUE' : 'FALSE') . "\n\n";

$isProductionDomain = ($test1 || $test2 || $test3 || $test4 || $test5);
echo "FINAL RESULT: isProductionDomain = " . ($isProductionDomain ? 'TRUE' : 'FALSE') . "\n\n";

// Now test path_helper.php
echo "=== TESTING path_helper.php ===\n";
echo "Unsetting any existing variables...\n";
unset($basePath, $apiPath, $cssPath, $imgPath, $publicPath);

echo "Including path_helper.php...\n";
require_once __DIR__ . '/header/includes/path_helper.php';

echo "\nRESULT AFTER path_helper.php:\n";
echo "basePath: [" . ($basePath ?? 'NOT SET') . "]\n";
echo "apiPath: [" . ($apiPath ?? 'NOT SET') . "]\n";
echo "cssPath: [" . ($cssPath ?? 'NOT SET') . "]\n";
echo "imgPath: [" . ($imgPath ?? 'NOT SET') . "]\n";
echo "publicPath: [" . ($publicPath ?? 'NOT SET') . "]\n\n";

// Expected vs Actual
echo "=== EXPECTED vs ACTUAL ===\n";
if ($isProductionDomain) {
    echo "Expected basePath: '' (empty)\n";
    echo "Actual basePath: [" . ($basePath ?? 'NOT SET') . "]\n";
    if (($basePath ?? '') === '') {
        echo "Status: ✓ CORRECT\n";
    } else {
        echo "Status: ✗ INCORRECT - Should be empty for production\n";
    }
} else {
    echo "Expected basePath: '/public-safety-campaign-system' (for localhost)\n";
    echo "Actual basePath: [" . ($basePath ?? 'NOT SET') . "]\n";
    if (($basePath ?? '') === '/public-safety-campaign-system') {
        echo "Status: ✓ CORRECT\n";
    } else {
        echo "Status: ✗ INCORRECT\n";
    }
}

echo "\n=== HTML COMMENT OUTPUT ===\n";
echo "Check page source for these comments:\n";
echo "<!-- BASEPATH_COMPUTED: ... -->\n";
echo "<!-- HOST_DETECTED: ... -->\n";
echo "<!-- SERVER_NAME: ... -->\n";
echo "<!-- FINAL_BASEPATH: ... -->\n";
echo "<!-- PRODUCTION_DETECTED: ... -->\n";

