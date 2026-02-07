<?php

require 'src/Config/db_connect.php';

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName),
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = ['ai_model_versions', 'ai_training_logs', 'ai_prediction_cache', 'ai_prediction_requests'];

echo "Verifying Google AutoML Integration tables...\n\n";

foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        $count = $countStmt->fetch()['cnt'];
        echo "✓ Table '$table' exists ($count rows)\n";
    } else {
        echo "✗ Table '$table' NOT found\n";
    }
}

echo "\n✅ Verification complete!\n";









