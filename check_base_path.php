<?php
/**
 * Quick verification script to check base path detection
 * Upload this to production root and visit: https://campaign.alertaraqc.com/check_base_path.php
 * Delete this file after verification
 */

require_once __DIR__ . '/header/includes/path_helper.php';

header('Content-Type: text/plain');

echo "=== Base Path Detection Check ===\n\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "\n\n";

echo "Detection Results:\n";
$host = $_SERVER['HTTP_HOST'] ?? '';
$hostLower = strtolower($host);
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$isProductionDomain = strpos($hostLower, 'campaign.alertaraqc.com') !== false || 
                strpos($hostLower, 'alertaraqc.com') !== false ||
                strpos($hostLower, '.alertaraqc.com') !== false;
$cssAtRoot = $documentRoot && file_exists(rtrim($documentRoot, '/') . '/header/css/global.css');
$cssAtSubdir = $documentRoot && file_exists(rtrim($documentRoot, '/') . '/public-safety-campaign-system/header/css/global.css');
$useRootPath = $isProductionDomain || ($cssAtRoot && !$cssAtSubdir);

echo "Host (lowercase): $hostLower\n";
echo "Is Production Domain: " . ($isProductionDomain ? 'YES' : 'NO') . "\n";
echo "CSS at root (/header/css/global.css): " . ($cssAtRoot ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "CSS at subdir (/public-safety-campaign-system/header/css/global.css): " . ($cssAtSubdir ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "Will use root path: " . ($useRootPath ? 'YES' : 'NO') . "\n\n";

echo "Final Values:\n";
echo "basePath: [" . $basePath . "]\n";
echo "cssPath: [" . $cssPath . "]\n";
echo "imgPath: [" . $imgPath . "]\n";
echo "apiPath: [" . $apiPath . "]\n\n";

echo "Expected for Production:\n";
echo "basePath should be: [] (empty)\n";
echo "cssPath should be: [/header/css]\n";
echo "If basePath is [/public-safety-campaign-system], the fix is NOT active.\n";

