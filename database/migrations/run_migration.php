<?php
/**
 * Database Migration Runner
 * Automatically runs the fix_events_schema.sql migration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require_once __DIR__ . '/../../src/Config/db_connect.php';

// Enable buffered queries to avoid the error
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

echo "<h1>Event Management System - Database Migration</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}</style>";

try {
    // Read the SQL migration file
    $sqlFile = __DIR__ . '/fix_events_schema_v2.sql';
    
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
            if (preg_match('/^(CREATE TABLE|ALTER TABLE|CREATE INDEX)/i', $statement, $matches)) {
                echo "<span class='success'>✓</span> " . $matches[1] . " executed successfully\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            
            // Ignore "already exists" errors as they're expected
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
        echo "<h3 class='error'>Errors Details:</h3>";
        echo "<pre>";
        foreach ($errors as $error) {
            echo "Statement: " . $error['statement'] . "\n";
            echo "Error: " . $error['error'] . "\n\n";
        }
        echo "</pre>";
    }
    
    // Verify tables were created
    echo "<h2>Verifying Migration...</h2>";
    echo "<pre>";
    
    $tablesToCheck = [
        'campaign_department_events',
        'campaign_department_event_facilitators',
        'campaign_department_event_audience_segments',
        'campaign_department_event_agency_coordination',
        'campaign_department_attendance',
        'campaign_department_event_conflicts',
        'campaign_department_event_audit_log',
        'campaign_department_event_integration_checkpoints'
    ];
    
    foreach ($tablesToCheck as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✓</span> Table exists: $table\n";
        } else {
            echo "<span class='error'>✗</span> Table missing: $table\n";
        }
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>✓ Migration Completed!</h2>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Use Agency Coordination features</li>";
    echo "<li>Use Attendance Tracking</li>";
    echo "<li>Add facilitators to events</li>";
    echo "<li>Link audience segments to events</li>";
    echo "<li>View event details and edit events</li>";
    echo "</ul>";
    
    echo "<p><a href='/public/events.php' style='color:#4ec9b0;'>Go to Events Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Migration Failed!</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
