<?php
// Simplest possible test
header('Content-Type: application/json');
echo json_encode([
    'status' => 'OK',
    'message' => 'PHP is working',
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'time' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
], JSON_PRETTY_PRINT);
