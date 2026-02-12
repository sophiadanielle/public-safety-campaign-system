<?php
/**
 * API Gateway - Alternative entry point for /api/* requests
 * This file handles API requests when nginx can't be configured
 * 
 * Usage: Configure your application to call /api.php?path=/api/v1/auth/login
 * Or set up a simple rewrite: /api/* -> /api.php
 */

// Get the requested path from query string or PATH_INFO
$requestPath = $_GET['path'] ?? $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';

// Clean up the path
$requestPath = parse_url($requestPath, PHP_URL_PATH);

// If path doesn't start with /api/, prepend it
if (strpos($requestPath, '/api/') !== 0) {
    $requestPath = '/api' . $requestPath;
}

// Set the REQUEST_URI to the API path so index.php can route it
$_SERVER['REQUEST_URI'] = $requestPath;
$_SERVER['PATH_INFO'] = $requestPath;

// Include the main index.php which will handle the routing
require __DIR__ . '/index.php';
