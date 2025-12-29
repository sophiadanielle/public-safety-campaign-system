<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Config/db_connect.php';

$sqlFile = __DIR__ . '/migrations/018_notifications_system.sql';
$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "Migration 018_notifications_system.sql executed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}



