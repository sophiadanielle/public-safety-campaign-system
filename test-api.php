<?php
/**
 * Simple test file to check if nginx can serve PHP files
 * Access this at: https://campaign.alertaraqc.com/test-api.php
 */

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'PHP is working!',
    'server' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'NOT SET',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'NOT SET',
        'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'NOT SET',
        'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'NOT SET',
        'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'NOT SET'
    ]
]);
