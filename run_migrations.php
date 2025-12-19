<?php

/**
 * Database Migration Runner
 * 
 * This script runs all database migrations in order.
 * Usage: php run_migrations.php
 */

declare(strict_types=1);

// Load database configuration
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'LGU'; // Default to LGU as per db.sql
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

// Create database connection (without database name first, to create DB if needed)
$dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);

try {
    $pdo = new PDO($dsnNoDb, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '{$dbName}' ready\n";
    
    // Switch to the database
    $pdo->exec("USE `{$dbName}`");
    
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Define migration files in order
$migrations = [
    '001_initial_schema.sql',
    '011_complete_schema_update.sql',  // Run after initial schema
    '012_seed_data.sql',                // Seed data last
];

$migrationDir = __DIR__ . '/migrations';

echo "\n=== Running Database Migrations ===\n\n";

foreach ($migrations as $migrationFile) {
    $filePath = $migrationDir . '/' . $migrationFile;
    
    if (!file_exists($filePath)) {
        echo "⚠ Skipping {$migrationFile} (file not found)\n";
        continue;
    }
    
    echo "→ Running {$migrationFile}...\n";
    
    try {
        $sql = file_get_contents($filePath);
        
        // Remove MySQL-specific commands that PDO doesn't support
        $sql = preg_replace('/^CREATE DATABASE.*?;$/mi', '', $sql);
        $sql = preg_replace('/^USE.*?;$/mi', '', $sql);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !preg_match('/^--/', $stmt)
        );
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $pdo->exec($statement);
            }
        }
        
        $pdo->commit();
        echo "  ✓ {$migrationFile} completed successfully\n";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "  ✗ Error in {$migrationFile}: " . $e->getMessage() . "\n";
        echo "  Continuing with next migration...\n";
    }
}

echo "\n=== Migration Process Complete ===\n";
echo "✓ Database is ready to use!\n\n";








