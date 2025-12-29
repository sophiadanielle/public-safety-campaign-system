<?php
/**
 * Diagnostic script to test Content API endpoint
 * Run this from command line: php test_content_api.php
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Config/db_connect.php';

echo "Testing Content API Endpoint\n";
echo "============================\n\n";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM content_items");
    $result = $stmt->fetch();
    echo "✓ Database connection: OK\n";
    echo "  Content items in database: " . $result['count'] . "\n\n";
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test if content_tags table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'content_tags'");
    $exists = $stmt->rowCount() > 0;
    echo ($exists ? "✓" : "⚠") . " content_tags table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
} catch (PDOException $e) {
    echo "⚠ Could not check content_tags table: " . $e->getMessage() . "\n";
}

// Test if tags table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'tags'");
    $exists = $stmt->rowCount() > 0;
    echo ($exists ? "✓" : "⚠") . " tags table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
} catch (PDOException $e) {
    echo "⚠ Could not check tags table: " . $e->getMessage() . "\n";
}

// Test the actual query
echo "\nTesting Content Query:\n";
try {
    $sql = 'SELECT ci.id, ci.title, ci.body, ci.content_type, ci.visibility, ci.created_at, 
                   a.file_path, a.mime_type, ci.campaign_id
            FROM content_items ci
            LEFT JOIN attachments a ON a.content_item_id = ci.id
            ORDER BY ci.created_at DESC
            LIMIT 5';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "✓ Query executed successfully\n";
    echo "  Found " . count($results) . " content items\n";
    
    if (count($results) > 0) {
        echo "\nSample content item:\n";
        print_r($results[0]);
    }
} catch (PDOException $e) {
    echo "✗ Query failed: " . $e->getMessage() . "\n";
    echo "  SQL: " . $sql . "\n";
}

echo "\n============================\n";
echo "Diagnostic complete.\n";















