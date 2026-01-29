<?php
// VERSION: 2025-01-XX - Production fix with deterministic base path
// This version forces empty basePath for alertaraqc.com domains
// CRITICAL: Production domain detection is HIGHEST PRIORITY and ALWAYS overrides

// DIAGNOSTIC: Log file being executed
error_log("PATH_HELPER USED: " . __FILE__);
error_log("PATH_HELPER VERSION: 2025-01-XX-PRODUCTION-FIX-V2");

// CHECK GLOBAL FLAG FIRST (set by index.php before this file is included)
if (isset($GLOBALS['FORCE_PRODUCTION_BASEPATH']) && $GLOBALS['FORCE_PRODUCTION_BASEPATH'] === true) {
    $basePath = '';
    $apiPath = '/index.php';
    $cssPath = '/header/css';
    $imgPath = '/header/images';
    $publicPath = '/public';
    error_log("PATH_HELPER: Using global FORCE_PRODUCTION_BASEPATH flag - basePath set to empty");
    echo "<!-- FORCE_PRODUCTION_FLAG: true -->\n";
    echo "<!-- BASEPATH_COMPUTED: $basePath -->\n";
    echo "<!-- FINAL_BASEPATH: $basePath -->\n";
    return; // Exit early - production mode forced
}

// STEP 1: PRODUCTION DOMAIN DETECTION (HIGHEST PRIORITY - RUNS FIRST)
// NUCLEAR APPROACH: If NOT localhost, assume production
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$serverName = strtolower($_SERVER['SERVER_NAME'] ?? '');
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Check if this is DEFINITELY localhost
$isDefinitelyLocalhost = (
    strpos($host, 'localhost') !== false ||
    $host === '127.0.0.1' ||
    strpos($host, '.local') !== false ||
    strpos($host, 'xampp') !== false ||
    strpos($host, 'wamp') !== false ||
    strpos($serverName, 'localhost') !== false ||
    $serverName === '127.0.0.1'
);

// If NOT localhost, it's production (inverse logic - simpler and more reliable)
$isProductionDomain = !$isDefinitelyLocalhost && $host !== '';

// Also check explicit production domain indicators
if (!$isProductionDomain) {
    $isProductionDomain = (
        strpos($host, 'alertaraqc.com') !== false ||
        strpos($serverName, 'alertaraqc.com') !== false ||
        strpos($requestUri, 'alertaraqc.com') !== false ||
        strpos($host, 'campaign.') !== false
    );
}

// STEP 2: If production domain, FORCE empty basePath immediately (no other checks)
if ($isProductionDomain) {
    // FORCE override - unset any existing value first
    unset($basePath, $apiPath, $cssPath, $imgPath, $publicPath);
    
    $basePath = '';
    $apiPath = '/index.php';
    $cssPath = '/header/css';
    $imgPath = '/header/images';
    $publicPath = '/public';
    
    error_log("PRODUCTION DOMAIN DETECTED - FORCING EMPTY BASEPATH");
    error_log("HOST: $host, SERVER_NAME: $serverName, REQUEST_URI: $requestUri");
    error_log("BASEPATH FINAL: $basePath");
    
    echo "<!-- BASEPATH_COMPUTED: $basePath -->\n";
    echo "<!-- HOST_DETECTED: " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'NOT SET') . " -->\n";
    echo "<!-- SERVER_NAME: " . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'NOT SET') . " -->\n";
    echo "<!-- FINAL_BASEPATH: $basePath -->\n";
    echo "<!-- PRODUCTION_MODE: true -->\n";
    
    // CRITICAL: Don't return - continue to set variables to ensure they're defined
    // But skip all other logic
    goto skip_to_end;
}

// STEP 3: Check for BASE_PATH in environment variables (only if not production)
// This allows production to set BASE_PATH= in .env to force empty base path
if (isset($_ENV['BASE_PATH']) || getenv('BASE_PATH') !== false) {
    $envBasePath = $_ENV['BASE_PATH'] ?? getenv('BASE_PATH');
    $basePath = $envBasePath === '' ? '' : rtrim($envBasePath, '/');
    error_log("BASE_PATH from environment: $basePath");
} else {
    // STEP 4: Auto-detect from HTTP_HOST (only if not production and no env var)
    // LOCALHOST: Check for localhost or 127.0.0.1
    $isLocalhost = (strpos($host, 'localhost') !== false) || 
                   (strpos($serverName, 'localhost') !== false) ||
                   ($host === '127.0.0.1') ||
                   ($serverName === '127.0.0.1');
    
    if ($isLocalhost) {
        $basePath = '/public-safety-campaign-system';
        error_log("LOCALHOST DETECTED - USING SUBDIRECTORY");
    } else {
        // DEFAULT: Empty base path for all other domains (assume production)
        $basePath = '';
        error_log("DEFAULT: EMPTY BASEPATH (non-localhost, non-production domain)");
    }
}

// STEP 5: Define all paths based on basePath
$apiPath = $basePath . '/index.php';
$cssPath = $basePath . '/header/css';
$imgPath = $basePath . '/header/images';
$publicPath = $basePath . '/public';

// STEP 6: FINAL PRODUCTION CHECK (safety net - should never trigger if step 1 worked)
// This is a safety check in case production domain was missed earlier
if (isset($_SERVER['HTTP_HOST'])) {
    $finalCheckHost = strtolower($_SERVER['HTTP_HOST']);
    if (strpos($finalCheckHost, 'alertaraqc.com') !== false) {
        $basePath = '';
        $apiPath = '/index.php';
        $cssPath = '/header/css';
        $imgPath = '/header/images';
        $publicPath = '/public';
        error_log("SAFETY NET: Production domain detected in final check - FORCED EMPTY BASEPATH");
    }
}

skip_to_end:

// DIAGNOSTIC: Log final computed value
error_log("BASEPATH FINAL: $basePath");
error_log("HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET'));
error_log("SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET'));
error_log("CONTAINS_ALERTARAQC: " . ($isProductionDomain ? 'YES' : 'NO'));

// ABSOLUTE FINAL OVERRIDE: One more check before we finish
// This is the absolute last chance to fix production
if (isset($_SERVER['HTTP_HOST'])) {
    $absoluteFinalHost = strtolower($_SERVER['HTTP_HOST']);
    if (strpos($absoluteFinalHost, 'alertaraqc.com') !== false || 
        strpos($absoluteFinalHost, 'campaign.') !== false) {
        $basePath = '';
        $apiPath = '/index.php';
        $cssPath = '/header/css';
        $imgPath = '/header/images';
        $publicPath = '/public';
        error_log("ABSOLUTE FINAL OVERRIDE: Forced empty basePath");
    }
}

echo "<!-- BASEPATH_COMPUTED: $basePath -->\n";
echo "<!-- HOST_DETECTED: " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'NOT SET') . " -->\n";
echo "<!-- SERVER_NAME: " . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'NOT SET') . " -->\n";
echo "<!-- FINAL_BASEPATH: $basePath -->\n";
echo "<!-- PRODUCTION_DETECTED: " . ($isProductionDomain ? 'YES' : 'NO') . " -->\n";













