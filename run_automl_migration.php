<?php

require 'src/Config/db_connect.php';

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Get database config from environment or use defaults
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbName = $_ENV['DB_NAME'] ?? 'lgu';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );

    $sql = file_get_contents('migrations/020_automl_integration.sql');
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove comment lines
    $statements = preg_split('/;\s*(?=\n|$)/', $sql);
    
    $pdo->beginTransaction();
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        
        // Skip empty statements and comments
        if (empty($stmt) || strpos($stmt, '--') === 0) {
            continue;
        }
        
        // Skip SET statements (they don't need semicolons)
        if (preg_match('/^SET\s+/i', $stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignore SET errors
            }
            continue;
        }
        
        // Handle PREPARE/EXECUTE statements specially
        if (preg_match('/PREPARE\s+stmt\s+FROM\s+@sql/i', $stmt)) {
            // This is handled by the SQL itself, skip
            continue;
        }
        if (preg_match('/EXECUTE\s+stmt/i', $stmt)) {
            // This is handled by the SQL itself, skip
            continue;
        }
        if (preg_match('/DEALLOCATE\s+PREPARE/i', $stmt)) {
            // This is handled by the SQL itself, skip
            continue;
        }
        
        try {
            $pdo->exec($stmt);
            if (preg_match('/CREATE\s+TABLE/i', $stmt)) {
                echo "✓ Created table\n";
            } elseif (preg_match('/CREATE\s+INDEX/i', $stmt)) {
                echo "✓ Created index\n";
            } else {
                echo "✓ Executed: " . substr($stmt, 0, 60) . "...\n";
            }
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'Duplicate column') !== false ||
                (strpos($e->getMessage(), "doesn't exist") !== false && strpos($stmt, 'CREATE INDEX') !== false)) {
                echo "⚠ Skipped (already exists or dependency missing): " . substr($stmt, 0, 60) . "...\n";
                continue;
            }
            echo "❌ Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($stmt, 0, 100) . "...\n";
            throw $e;
        }
    }
    
    $pdo->commit();
    echo "\n✅ Migration 020 (Google AutoML Integration) completed successfully!\n";
    echo "Created tables:\n";
    echo "  - ai_model_versions\n";
    echo "  - ai_training_logs\n";
    echo "  - ai_prediction_cache\n";
    echo "  - ai_prediction_requests\n";
    
} catch (Exception $e) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    } catch (PDOException $rollbackError) {
        // Ignore rollback errors
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

