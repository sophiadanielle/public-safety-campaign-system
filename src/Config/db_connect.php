<?php

declare(strict_types=1);

// Simple PDO connection helper; replace placeholders with real values or load from environment.

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'LGU'; // Changed to match migration database name
$dbUser = getenv('DB_USERNAME') ?: 'root'; // XAMPP default
$dbPass = getenv('DB_PASSWORD') ?: ''; // XAMPP default (usually empty)

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Connection OK (for debugging only; remove echo in production)
    if (PHP_SAPI === 'cli') {
        echo "Connected to {$dbName} at {$dbHost}:{$dbPort}" . PHP_EOL;
    }
} catch (PDOException $e) {
    // Log error instead of outputting HTML
    error_log('Database connection failed: ' . $e->getMessage());
    
    // If we're in API context, return JSON error
    if (PHP_SAPI !== 'cli' && strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Otherwise, die with message (for CLI or non-API contexts)
    die('Database connection failed: ' . $e->getMessage());
}


