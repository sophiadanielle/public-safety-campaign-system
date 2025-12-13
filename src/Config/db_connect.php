<?php

declare(strict_types=1);

// Simple PDO connection helper; replace placeholders with real values or load from environment.

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'campaign_db';
$dbUser = getenv('DB_USERNAME') ?: 'campaign_user';
$dbPass = getenv('DB_PASSWORD') ?: 'changeme';

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
    // In production, log this instead of echoing.
    die('Database connection failed: ' . $e->getMessage());
}


