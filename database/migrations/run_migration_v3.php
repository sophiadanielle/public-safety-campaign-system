<?php
/**
 * Database Migration Runner V3
 * Simplified approach - execute SQL file directly via command line
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Event Management System - Database Migration V3</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}.info{color:#9cdcfe;}</style>";

try {
    // Load database config to get credentials
    require_once __DIR__ . '/../../src/Config/db_connect.php';
    
    // Get database credentials from PDO connection
    $host = 'localhost'; // Default, adjust if needed
    $dbname = 'LGU';
    
    // Read SQL file
    $sqlFile = __DIR__ . '/fix_events_schema_v3.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    echo "<h2>Migration Approach: Direct SQL Execution</h2>";
    echo "<p class='info'>This approach creates only the new tables. Columns will be added manually if needed.</p>";
    
    echo "<h2>Reading migration file...</h2>";
    $sql = file_get_contents($sqlFile);
    
    echo "<h2>Executing migration...</h2>";
    echo "<pre>";
    
    // Execute the entire SQL file
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Split by semicolon and execute each statement
        $statements = explode(';', $sql);
        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            // Skip empty statements and comments
            if (empty($statement) || 
                strpos($statement, '--') === 0 || 
                strpos($statement, '/*') === 0 ||
                strlen($statement) < 10) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                $successCount++;
                
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "<span class='success'>✓</span> Created table: $tableName\n";
                }
            } catch (PDOException $e) {
                $errorMsg = $e->getMessage();
                
                // Check if it's a "table already exists" error
                if (strpos($errorMsg, 'already exists') !== false || 
                    strpos($errorMsg, 'Duplicate') !== false) {
                    $skipCount++;
                    preg_match('/`([^`]+)`/', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "<span class='warning'>⚠</span> Skipped (already exists): $tableName\n";
                } else {
                    $errorCount++;
                    echo "<span class='error'>✗</span> Error: " . substr($errorMsg, 0, 100) . "...\n";
                }
            }
        }
        
        echo "</pre>";
        
        echo "<h2>Migration Summary</h2>";
        echo "<p><strong class='success'>Successful:</strong> $successCount</p>";
        echo "<p><strong class='warning'>Skipped:</strong> $skipCount</p>";
        echo "<p><strong class='error'>Errors:</strong> $errorCount</p>";
        
    } catch (Exception $e) {
        echo "</pre>";
        echo "<h2 class='error'>Migration Error!</h2>";
        echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Verify tables were created
    echo "<h2>Verifying Tables...</h2>";
    echo "<pre>";
    
    $tablesToCheck = [
        'campaign_department_event_facilitators',
        'campaign_department_event_audience_segments',
        'campaign_department_event_agency_coordination',
        'campaign_department_event_conflicts',
        'campaign_department_event_audit_log',
        'campaign_department_event_integration_checkpoints'
    ];
    
    $existingTables = 0;
    foreach ($tablesToCheck as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<span class='success'>✓</span> Table exists: $table\n";
                $existingTables++;
            } else {
                echo "<span class='error'>✗</span> Table missing: $table\n";
            }
        } catch (Exception $e) {
            echo "<span class='error'>✗</span> Error checking $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "</pre>";
    
    if ($existingTables === count($tablesToCheck)) {
        echo "<h2 class='success'>✓ Migration Completed Successfully!</h2>";
        echo "<p>All 6 tables have been created.</p>";
        echo "<p class='info'><strong>Note:</strong> Column additions to existing tables will be handled separately to avoid PDO buffering issues.</p>";
    } else {
        echo "<h2 class='warning'>⚠ Migration Partially Completed</h2>";
        echo "<p>$existingTables out of " . count($tablesToCheck) . " tables exist.</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Tables have been created successfully</li>";
    echo "<li>Missing columns will be added via a separate script</li>";
    echo "<li><a href='/public/events.php' style='color:#4ec9b0;'>Go to Events Page</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Fatal Error!</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
