<?php
/**
 * API Entry Point - Direct access via /api/index.php
 * This file acts as a proxy to the main index.php for API requests
 * Works around nginx configuration limitations
 */

// Get the request path after /api/
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Extract the API path
// Example: /api/index.php/v1/auth/login -> /api/v1/auth/login
if (strpos($requestUri, '/api/index.php') !== false) {
    $apiPath = str_replace('/api/index.php', '/api', $requestUri);
} elseif (strpos($requestUri, '/api/') !== false) {
    $apiPath = $requestUri;
} else {
    $apiPath = '/api' . $requestUri;
}

// Clean up the path
$apiPath = parse_url($apiPath, PHP_URL_PATH);

// Override REQUEST_URI for the main index.php
$_SERVER['REQUEST_URI'] = $apiPath;
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Include the main application entry point
require __DIR__ . '/../index.php';
