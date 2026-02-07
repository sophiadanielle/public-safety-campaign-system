<?php
/**
 * Production Path Diagnostic Script
 * Upload this to production root and access it to see what's happening
 */

header('Content-Type: text/plain');

echo "=== PRODUCTION PATH DIAGNOSTIC ===\n\n";

echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n\n";

// Check if path_helper.php exists
$pathHelperFile = __DIR__ . '/header/includes/path_helper.php';
echo "path_helper.php location: $pathHelperFile\n";
echo "File exists: " . (file_exists($pathHelperFile) ? 'YES' : 'NO') . "\n";

if (file_exists($pathHelperFile)) {
    echo "File size: " . filesize($pathHelperFile) . " bytes\n";
    echo "File modified: " . date('Y-m-d H:i:s', filemtime($pathHelperFile)) . "\n";
    
    // Check for version string
    $content = file_get_contents($pathHelperFile);
    if (strpos($content, 'PATH_HELPER_VERSION: 2024-01-XX-PRODUCTION-FIX') !== false) {
        echo "Version: NEW (2024-01-XX-PRODUCTION-FIX) ✓\n";
    } else {
        echo "Version: OLD (version string not found) ✗\n";
    }
    
    // Check for production domain check
    if (strpos($content, 'alertaraqc.com') !== false) {
        echo "Contains production domain check: YES ✓\n";
    } else {
        echo "Contains production domain check: NO ✗\n";
    }
    
    // Check for final override
    if (strpos($content, 'ABSOLUTE FINAL CHECK') !== false) {
        echo "Contains final override: YES ✓\n";
    } else {
        echo "Contains final override: NO ✗\n";
    }
} else {
    echo "ERROR: path_helper.php not found!\n";
}

echo "\n=== Testing path_helper.php ===\n";

// Unset any existing basePath
unset($basePath, $apiPath, $cssPath, $imgPath, $publicPath);

// Include path_helper
if (file_exists($pathHelperFile)) {
    require_once $pathHelperFile;
    
    echo "basePath: [" . ($basePath ?? 'NOT SET') . "]\n";
    echo "cssPath: [" . ($cssPath ?? 'NOT SET') . "]\n";
    echo "imgPath: [" . ($imgPath ?? 'NOT SET') . "]\n";
    
    if (isset($basePath)) {
        if ($basePath === '') {
            echo "\n✓ SUCCESS: basePath is empty (correct for production)\n";
        } else {
            echo "\n✗ ERROR: basePath is '$basePath' (should be empty for production)\n";
        }
    } else {
        echo "\n✗ ERROR: basePath is not set\n";
    }
} else {
    echo "Cannot test - path_helper.php not found\n";
}

echo "\n=== Environment Variables ===\n";
if (isset($_ENV['BASE_PATH'])) {
    echo "BASE_PATH from \$_ENV: [" . $_ENV['BASE_PATH'] . "]\n";
} else {
    echo "BASE_PATH from \$_ENV: NOT SET\n";
}

if (getenv('BASE_PATH') !== false) {
    echo "BASE_PATH from getenv(): [" . getenv('BASE_PATH') . "]\n";
} else {
    echo "BASE_PATH from getenv(): NOT SET\n";
}

echo "\n=== Expected vs Actual ===\n";
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if (strpos($host, 'alertaraqc.com') !== false) {
    echo "Domain: Production (alertaraqc.com)\n";
    echo "Expected basePath: '' (empty)\n";
    echo "Actual basePath: [" . ($basePath ?? 'NOT SET') . "]\n";
    if (($basePath ?? '') === '') {
        echo "Status: ✓ CORRECT\n";
    } else {
        echo "Status: ✗ INCORRECT - Production should have empty basePath\n";
    }
} else {
    echo "Domain: Development/Localhost\n";
    echo "Expected basePath: '/public-safety-campaign-system' (for localhost)\n";
    echo "Actual basePath: [" . ($basePath ?? 'NOT SET') . "]\n";
}

