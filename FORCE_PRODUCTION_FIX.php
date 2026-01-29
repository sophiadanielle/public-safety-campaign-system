<?php
/**
 * EMERGENCY FIX: Force production basePath
 * 
 * If the automatic detection is still failing, add this at the VERY TOP of index.php
 * (before any other includes or code):
 * 
 * if (isset($_SERVER['HTTP_HOST']) && strpos(strtolower($_SERVER['HTTP_HOST']), 'alertaraqc.com') !== false) {
 *     $GLOBALS['FORCE_PRODUCTION_BASEPATH'] = true;
 * }
 * 
 * Then in path_helper.php, check for this flag at the very beginning.
 */

// Test script - run this to see what's happening
header('Content-Type: text/plain');

echo "=== FORCE PRODUCTION FIX TEST ===\n\n";

$host = strtolower($_SERVER['HTTP_HOST'] ?? 'NOT SET');
echo "HTTP_HOST: $host\n";

$isProduction = (
    strpos($host, 'alertaraqc.com') !== false ||
    strpos($host, 'campaign.') !== false ||
    ($host !== 'NOT SET' && $host !== '' && strpos($host, 'localhost') === false && $host !== '127.0.0.1')
);

echo "Is Production: " . ($isProduction ? 'YES' : 'NO') . "\n\n";

if ($isProduction) {
    echo "✓ Production detected - basePath should be EMPTY\n";
    echo "If basePath is still '/public-safety-campaign-system', the fix is not working.\n";
} else {
    echo "✗ Production NOT detected - check HTTP_HOST value\n";
}

