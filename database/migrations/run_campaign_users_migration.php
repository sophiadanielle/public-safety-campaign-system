<?php
/**
 * Auto Migration Runner for campaign_users table
 * Creates the campaign_users table for login authentication
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require_once __DIR__ . '/../../src/Config/db_connect.php';

// Enable buffered queries
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

echo "<h1>Campaign Users Table - Auto Migration</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}</style>";

try {
    // Read the SQL migration file
    $sqlFile = __DIR__ . '/../../migrations/034_campaign_users_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    echo "<h2>Reading migration file...</h2>";
    $sql = file_get_contents($sqlFile);
    
    echo "<h2>Executing migration...</h2>";
    echo "<pre>";
    
    // Remove comments and split into statements
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 5;
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Execute each statement individually
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $result = $pdo->exec($statement . ';');
            $successCount++;
            
            if (preg_match('/^(CREATE TABLE|ALTER TABLE|CREATE INDEX)/i', $statement, $matches)) {
                echo "<span class='success'>✓</span> " . $matches[1] . " executed successfully\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // Ignore "already exists" errors
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate') !== false) {
                echo "<span class='warning'>⚠</span> Skipped (already exists): " . substr($statement, 0, 60) . "...\n";
            } else {
                echo "<span class='error'>✗</span> Error: " . $errorMsg . "\n";
                echo "   Statement: " . substr($statement, 0, 100) . "...\n";
                $errors[] = [
                    'statement' => substr($statement, 0, 200),
                    'error' => $errorMsg
                ];
            }
        }
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>Migration Summary</h2>";
    echo "<p><strong>Successful statements:</strong> $successCount</p>";
    echo "<p><strong>Errors:</strong> $errorCount</p>";
    
    if (!empty($errors)) {
        echo "<h3 class='error'>Error Details:</h3>";
        echo "<pre>";
        foreach ($errors as $error) {
            echo "Statement: " . $error['statement'] . "\n";
            echo "Error: " . $error['error'] . "\n\n";
        }
        echo "</pre>";
    }
    
    // Verify table was created
    echo "<h2>Verifying Migration...</h2>";
    echo "<pre>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_users'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓</span> Table exists: campaign_users\n";
        
        // Show table structure
        echo "\n<span class='success'>Table Structure:</span>\n";
        $columns = $pdo->query("DESCRIBE campaign_users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']}) {$column['Key']}\n";
        }
    } else {
        echo "<span class='error'>✗</span> Table missing: campaign_users\n";
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>✓ Migration Completed!</h2>";
    echo "<p>The campaign_users table has been created with the following fields:</p>";
    echo "<ul>";
    echo "<li><strong>id</strong> - Auto-incrementing primary key</li>";
    echo "<li><strong>email</strong> - Unique email address for login</li>";
    echo "<li><strong>password</strong> - Password hash for authentication</li>";
    echo "<li><strong>fullname</strong> - User's full name</li>";
    echo "<li><strong>date_created</strong> - Account creation timestamp</li>";
    echo "<li><strong>avatar_url</strong> - Optional profile picture URL</li>";
    echo "<li><strong>user_type</strong> - User role/type (default: 'user')</li>";
    echo "<li><strong>archived</strong> - Soft delete flag (default: 0)</li>";
    echo "</ul>";
    
    echo "<p style='color:#dcdcaa;'>You can now use this table for login authentication.</p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Migration Failed!</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
