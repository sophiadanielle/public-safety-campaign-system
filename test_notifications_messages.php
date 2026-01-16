<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Config/db_connect.php';

echo "=== NOTIFICATIONS & MESSAGES SYSTEM VERIFICATION ===\n\n";

// Check notifications table
try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM notifications');
    $count = $stmt->fetchColumn();
    echo "✅ Notifications table: {$count} records\n";
    
    // Check table structure
    $stmt = $pdo->query('DESCRIBE notifications');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Columns: " . implode(', ', $columns) . "\n";
} catch (Exception $e) {
    echo "❌ Notifications table error: " . $e->getMessage() . "\n";
}

echo "\n";

// Check conversations table
try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM conversations');
    $count = $stmt->fetchColumn();
    echo "✅ Conversations table: {$count} records\n";
    
    // Check table structure
    $stmt = $pdo->query('DESCRIBE conversations');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Columns: " . implode(', ', $columns) . "\n";
} catch (Exception $e) {
    echo "❌ Conversations table error: " . $e->getMessage() . "\n";
}

echo "\n";

// Check messages table
try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM messages');
    $count = $stmt->fetchColumn();
    echo "✅ Messages table: {$count} records\n";
    
    // Check table structure
    $stmt = $pdo->query('DESCRIBE messages');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Columns: " . implode(', ', $columns) . "\n";
} catch (Exception $e) {
    echo "❌ Messages table error: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";






