<?php
/**
 * Check if content items exist in database
 */

declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'LGU';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName),
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Connected to database '{$dbName}'\n\n";
    
    // Count all content items
    $count = $pdo->query("SELECT COUNT(*) FROM content_items")->fetchColumn();
    echo "→ Total content items in database: {$count}\n\n";
    
    // Show all content items
    $items = $pdo->query("SELECT id, title, content_type, approval_status, created_by FROM content_items ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "✗ No content items found!\n";
        echo "  → The database is empty. Need to insert sample data.\n";
    } else {
        echo "→ Found {$count} content items:\n";
        foreach ($items as $item) {
            echo "  - ID {$item['id']}: {$item['title']} ({$item['content_type']}, {$item['approval_status']})\n";
        }
    }
    
} catch (PDOException $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}






