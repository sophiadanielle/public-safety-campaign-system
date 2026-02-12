<?php
/**
 * API Entry Point - Direct access via /api/index.php
 * This file acts as a proxy to the main index.php for API requests
 * Works around nginx configuration limitations
 */

// Get the original request information
$originalUri = $_SERVER['REQUEST_URI'] ?? '';
$originalScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$pathInfo = $_SERVER['PATH_INFO'] ?? '';

error_log("API PROXY: Original REQUEST_URI = " . $originalUri);
error_log("API PROXY: Original SCRIPT_NAME = " . $originalScriptName);
error_log("API PROXY: Original PATH_INFO = " . $pathInfo);

// Determine the API path
// nginx may set PATH_INFO to /v1/auth/login when accessing /api/index.php/v1/auth/login
$apiPath = '';

if (!empty($pathInfo)) {
    // nginx set PATH_INFO - use it to construct the API path
    // PATH_INFO will be like: /v1/auth/login
    // We need to prepend /api to make it: /api/v1/auth/login
    $apiPath = '/api' . $pathInfo;
    error_log("API PROXY: Using PATH_INFO, constructed path = " . $apiPath);
} elseif (strpos($originalUri, '/api/index.php') !== false) {
    // Fallback: parse from REQUEST_URI
    // Example: /api/index.php/v1/auth/login -> /api/v1/auth/login
    $apiPath = str_replace('/api/index.php', '/api', $originalUri);
    // Remove query string
    $apiPath = parse_url($apiPath, PHP_URL_PATH);
    error_log("API PROXY: Using REQUEST_URI, constructed path = " . $apiPath);
} else {
    // Last resort
    $apiPath = $originalUri;
    error_log("API PROXY: Using original URI as-is = " . $apiPath);
}

error_log("API PROXY: Final API path = " . $apiPath);

// Set the global flag to indicate this is an API request
$GLOBALS['IS_API_REQUEST'] = true;
$GLOBALS['API_PATH'] = $apiPath;

// Override REQUEST_URI for the main index.php
$_SERVER['REQUEST_URI'] = $apiPath;
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['PATH_INFO'] = $apiPath;

error_log("API PROXY: Set REQUEST_URI to " . $_SERVER['REQUEST_URI']);
error_log("API PROXY: Set PATH_INFO to " . $_SERVER['PATH_INFO']);

// Include the main application entry point
require __DIR__ . '/../index.php';
