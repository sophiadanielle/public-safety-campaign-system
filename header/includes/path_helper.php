<?php
/**
 * Path Helper for Header/Sidebar Includes
 * Calculates correct paths for CSS, images, and links
 */

// Get the project base path relative to document root
$projectRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

// Calculate base path
if (strpos($projectRoot, $docRoot) === 0) {
    // Project is inside document root
    $basePath = str_replace($docRoot, '', $projectRoot);
} else {
    // Fallback: try to detect from script path
    $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
    if (strpos($scriptPath, $projectRoot) !== false) {
        $basePath = str_replace($docRoot, '', $scriptPath);
        $basePath = dirname($basePath); // Go up from public folder
    } else {
        // Last resort: use a default
        $basePath = '/public-safety-campaign-system';
    }
}

$basePath = rtrim($basePath, '/');

// Define asset paths
$cssPath = $basePath . '/header/css';
$imgPath = $basePath . '/header/images';
$publicPath = $basePath . '/public';
$apiPath = $basePath . '/index.php';








