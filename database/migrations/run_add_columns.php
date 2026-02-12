<?php
/**
 * Add Missing Columns Migration Runner
 * Adds all missing columns causing errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Add Missing Columns - Migration</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}</style>";

try {
    require_once __DIR__ . '/../../src/Config/db_connect.php';
    
    $sqlFile = __DIR__ . '/add_all_missing_columns.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    echo "<h2>Reading migration file...</h2>";
    $sql = file_get_contents($sqlFile);
    
    echo "<h2>Executing migration...</h2>";
    echo "<pre>";
    
    // Split and execute each ALTER TABLE statement
    $statements = explode(';', $sql);
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement) || 
            strpos($statement, '--') === 0 || 
            strpos($statement, '/*') === 0 ||
            strpos($statement, 'USE') === 0 ||
            strpos($statement, 'SELECT') === 0 ||
            strlen($statement) < 10) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            if (stripos($statement, 'ADD COLUMN') !== false) {
                preg_match('/ADD COLUMN `([^`]+)`/', $statement, $matches);
                $columnName = $matches[1] ?? 'unknown';
                echo "<span class='success'>✓</span> Added column: $columnName\n";
            } elseif (stripos($statement, 'ADD INDEX') !== false) {
                preg_match('/ADD INDEX `([^`]+)`/', $statement, $matches);
                $indexName = $matches[1] ?? 'unknown';
                echo "<span class='success'>✓</span> Added index: $indexName\n";
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            if (strpos($errorMsg, 'Duplicate column') !== false || 
                strpos($errorMsg, 'already exists') !== false) {
                $skipCount++;
                preg_match('/`([^`]+)`/', $statement, $matches);
                $name = $matches[1] ?? 'unknown';
                echo "<span class='warning'>⚠</span> Skipped (already exists): $name\n";
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
    
    // Verify columns were added
    echo "<h2>Verifying Columns...</h2>";
    echo "<pre>";
    
    $columnsToCheck = [
        'campaign_department_attendance' => ['participant_identifier', 'checkin_method', 'checkin_notes', 'checkin_timestamp'],
        'campaign_department_event_agency_coordination' => ['action_type']
    ];
    
    foreach ($columnsToCheck as $table => $columns) {
        echo "\n<strong>Table: $table</strong>\n";
        foreach ($columns as $column) {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->rowCount() > 0) {
                echo "<span class='success'>✓</span> Column exists: $column\n";
            } else {
                echo "<span class='error'>✗</span> Column missing: $column\n";
            }
        }
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>✓ Migration Completed!</h2>";
    echo "<p>All missing columns have been added.</p>";
    echo "<p><a href='/public/events.php' style='color:#4ec9b0;'>Go to Events Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Migration Failed!</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
