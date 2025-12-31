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
    $errorMessage = $e->getMessage();
    error_log('Database connection failed: ' . $errorMessage);
    error_log('Attempted connection to: ' . $dsn);
    error_log('User: ' . $dbUser);
    
    // If we're in API context, return JSON error with more details
    if (PHP_SAPI !== 'cli' && strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        
        // Provide more helpful error message
        $userFriendlyError = 'Database connection failed';
        if (strpos($errorMessage, 'Access denied') !== false) {
            $userFriendlyError = 'Database access denied. Please check your database credentials.';
        } elseif (strpos($errorMessage, 'Unknown database') !== false) {
            $userFriendlyError = 'Database not found. Please make sure the database "' . $dbName . '" exists.';
        } elseif (strpos($errorMessage, 'Connection refused') !== false || strpos($errorMessage, 'No connection') !== false) {
            $userFriendlyError = 'Cannot connect to database server. Please make sure MySQL/XAMPP is running.';
        }
        
        echo json_encode(['error' => $userFriendlyError]);
        exit;
    }
    
    // Otherwise, die with message (for CLI or non-API contexts)
    die('Database connection failed: ' . $errorMessage);
}


