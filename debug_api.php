<?php
/**
 * Debug script to test API routing
 * Access: http://localhost/public-safety-campaign-system/debug_api.php
 */

echo "<h2>API Route Debugging</h2>";
echo "<pre>";

echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n\n";

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
echo "Parsed REQUEST_URI: " . $requestUri . "\n";

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);
echo "Script Directory: " . $scriptDir . "\n";

// Test the same logic as index.php
if ($scriptDir !== '/' && $scriptDir !== '.') {
    if (strpos($requestUri, $scriptDir) === 0) {
        $requestUri = substr($requestUri, strlen($scriptDir));
    }
}

if (strpos($requestUri, '/index.php') === 0) {
    $requestUri = substr($requestUri, strlen('/index.php'));
} elseif (strpos($requestUri, 'index.php/') !== false) {
    $requestUri = substr($requestUri, strpos($requestUri, 'index.php/') + strlen('index.php'));
}

if ($requestUri === '' || ($requestUri[0] !== '/' && $requestUri !== '')) {
    $requestUri = '/' . $requestUri;
}

echo "Normalized URI: " . $requestUri . "\n\n";

// Test route matching
require __DIR__ . '/vendor/autoload.php';
$routes = require __DIR__ . '/src/Routes/content.php';

echo "Content Routes:\n";
foreach ($routes as $route) {
    $pattern = preg_replace('#\{([\w]+)\}#', '(?P<$1>[^/]+)', $route['path']);
    $pattern = '#^' . $pattern . '$#';
    $matches = preg_match($pattern, $requestUri, $matchResults);
    echo "  " . $route['method'] . " " . $route['path'] . " -> " . ($matches ? "MATCH" : "no match") . "\n";
    if ($matches) {
        echo "    Matched groups: " . print_r($matchResults, true) . "\n";
    }
}

echo "\n</pre>";


















