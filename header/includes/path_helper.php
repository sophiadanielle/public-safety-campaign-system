<?php
// Shared path configuration for local XAMPP and production domains.
// This file must stay silent because it is included before headers are finalized.

$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$serverName = strtolower($_SERVER['SERVER_NAME'] ?? '');

$isLocalhost = (
    strpos($host, 'localhost') !== false ||
    $host === '127.0.0.1' ||
    strpos($host, '.local') !== false ||
    strpos($host, 'xampp') !== false ||
    strpos($host, 'wamp') !== false ||
    strpos($serverName, 'localhost') !== false ||
    $serverName === '127.0.0.1'
);

$basePath = $isLocalhost ? '/public-safety-campaign-system' : '';

if (isset($_ENV['BASE_PATH']) || getenv('BASE_PATH') !== false) {
    $envBasePath = $_ENV['BASE_PATH'] ?? getenv('BASE_PATH');
    $basePath = $envBasePath === '' ? '' : rtrim((string) $envBasePath, '/');
}

if (strpos($host, 'alertaraqc.com') !== false || strpos($host, 'campaign.') !== false) {
    $basePath = '';
}

$apiPath = $basePath . '/index.php';
$cssPath = $basePath . '/header/css';
$imgPath = $basePath . '/header/images';
$publicPath = $basePath . '/public';
