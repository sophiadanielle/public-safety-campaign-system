<?php
// Prevent multiple includes from resetting basePath
if (isset($basePath)) {
    return;
}

// DIAGNOSTIC: Log file being executed
error_log("PATH_HELPER USED: " . __FILE__);
echo "<!-- PATH_HELPER_FILE: " . __FILE__ . " -->\n";

// HARD OVERRIDE: Force empty base path for production domain
// This takes absolute priority - no detection logic can override this
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$serverName = strtolower($_SERVER['SERVER_NAME'] ?? '');

// PRODUCTION DOMAIN CHECK: If domain contains alertaraqc.com, FORCE empty base path
if (strpos($host, 'alertaraqc.com') !== false || strpos($serverName, 'alertaraqc.com') !== false) {
    $basePath = '';
    error_log("PRODUCTION DOMAIN DETECTED - FORCING EMPTY BASEPATH");
} else {
    // LOCALHOST: Check for localhost or 127.0.0.1
    $isLocalhost = (strpos($host, 'localhost') !== false) || 
                   (strpos($serverName, 'localhost') !== false) ||
                   ($host === '127.0.0.1') ||
                   ($serverName === '127.0.0.1');
    
    if ($isLocalhost) {
        $basePath = '/public-safety-campaign-system';
        error_log("LOCALHOST DETECTED - USING SUBDIRECTORY");
    } else {
        // DEFAULT: Empty base path for all other domains (production)
        $basePath = '';
        error_log("DEFAULT: EMPTY BASEPATH FOR PRODUCTION");
    }
}

// Define all paths based on basePath
$apiPath = $basePath . '/index.php';
$cssPath = $basePath . '/header/css';
$imgPath = $basePath . '/header/images';
$publicPath = $basePath . '/public';

// DIAGNOSTIC: Log final computed value
error_log("BASEPATH FINAL: $basePath");
error_log("HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET'));
error_log("HOST_LOWER: $host");
error_log("CONTAINS_LOCALHOST: " . (strpos($host, 'localhost') !== false ? 'YES' : 'NO'));
echo "<!-- BASEPATH_COMPUTED: $basePath -->\n";
echo "<!-- HOST_DETECTED: " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'NOT SET') . " -->\n";













