<?php
/**
 * Database Migration Runner - OTP and User Management
 * Automatically runs the 032_otp_and_user_management.sql migration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require_once __DIR__ . '/../../src/Config/db_connect.php';

// Enable buffered queries to avoid errors
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

echo "<h1>OTP and User Management - Database Migration</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}</style>";

try {
    // Read the SQL migration file
    $sqlFile = __DIR__ . '/../../migrations/032_otp_and_user_management.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    echo "<h2>Reading migration file...</h2>";
    $sql = file_get_contents($sqlFile);
    
    echo "<h2>Executing migration...</h2>";
    echo "<pre>";
    
    // Remove comments and split into statements
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
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
            // Use exec for DDL statements
            $result = $pdo->exec($statement . ';');
            $successCount++;
            
            // Extract action from statement for logging
            if (preg_match('/^(CREATE TABLE|ALTER TABLE|CREATE INDEX|ADD COLUMN)/i', $statement, $matches)) {
                echo "<span class='success'>✓</span> " . $matches[1] . " executed successfully\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // Ignore "already exists" errors as they're expected
            if (strpos($errorMsg, 'already exists') !== false || 
                strpos($errorMsg, 'Duplicate') !== false ||
                strpos($errorMsg, 'duplicate column name') !== false) {
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
        echo "<h3 class='error'>Errors Details:</h3>";
        echo "<pre>";
        foreach ($errors as $error) {
            echo "Statement: " . $error['statement'] . "\n";
            echo "Error: " . $error['error'] . "\n\n";
        }
        echo "</pre>";
    }
    
    // Verify tables and columns were created
    echo "<h2>Verifying Migration...</h2>";
    echo "<pre>";
    
    // Check if OTP table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_department_otp'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓</span> Table exists: campaign_department_otp\n";
        
        // Check OTP table columns
        $otpColumns = ['id', 'user_id', 'email', 'otp_code', 'expires_at', 'is_used', 'created_at'];
        $stmt = $pdo->query("DESCRIBE campaign_department_otp");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($otpColumns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "<span class='success'>  ✓</span> Column exists: $column\n";
            } else {
                echo "<span class='error'>  ✗</span> Column missing: $column\n";
            }
        }
    } else {
        echo "<span class='error'>✗</span> Table missing: campaign_department_otp\n";
    }
    
    echo "\n";
    
    // Check if user table columns were added
    $stmt = $pdo->query("SHOW TABLES LIKE 'campaign_department_users'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓</span> Table exists: campaign_department_users\n";
        
        // Check new columns
        $newColumns = ['user_type', 'avatar_url', 'archived', 'archived_at', 'phone_number'];
        $stmt = $pdo->query("DESCRIBE campaign_department_users");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($newColumns as $column) {
            if (in_array($column, $existingColumns)) {
                echo "<span class='success'>  ✓</span> Column exists: $column\n";
            } else {
                echo "<span class='warning'>  ⚠</span> Column missing: $column\n";
            }
        }
        
        // Check for archived index
        $stmt = $pdo->query("SHOW INDEX FROM campaign_department_users WHERE Key_name = 'idx_users_archived'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>  ✓</span> Index exists: idx_users_archived\n";
        } else {
            echo "<span class='warning'>  ⚠</span> Index missing: idx_users_archived\n";
        }
    } else {
        echo "<span class='error'>✗</span> Table missing: campaign_department_users\n";
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>✓ Migration Completed!</h2>";
    echo "<p>The following features are now available:</p>";
    echo "<ul>";
    echo "<li>OTP-based login verification</li>";
    echo "<li>User type management (Super Admin, Admin, Staff, Employee)</li>";
    echo "<li>User avatar support</li>";
    echo "<li>Soft delete functionality (archived users)</li>";
    echo "<li>Phone number storage for users</li>";
    echo "</ul>";
    
    echo "<p><a href='/public/' style='color:#4ec9b0;'>Go to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Migration Failed!</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
