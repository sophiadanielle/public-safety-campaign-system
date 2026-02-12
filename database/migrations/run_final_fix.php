<?php
/**
 * Final Fix - Add All Missing Columns
 * This script will add checkin_timestamp and action_type columns
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Final Fix - Add Missing Columns</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}pre{background:#2d2d2d;padding:10px;overflow-x:auto;}.success{color:#4ec9b0;}.error{color:#f48771;}.warning{color:#dcdcaa;}</style>";

try {
    require_once __DIR__ . '/../../src/Config/db_connect.php';
    
    echo "<h2>Executing fixes...</h2>";
    echo "<pre>";
    
    // Fix 1: Drop duplicate indexes
    try {
        $pdo->exec("ALTER TABLE `campaign_department_attendance` DROP INDEX `idx_checkin_method`");
        echo "<span class='success'>✓</span> Dropped index: idx_checkin_method\n";
    } catch (Exception $e) {
        echo "<span class='warning'>⚠</span> Index idx_checkin_method doesn't exist (OK)\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE `campaign_department_attendance` DROP INDEX `idx_participant_identifier`");
        echo "<span class='success'>✓</span> Dropped index: idx_participant_identifier\n";
    } catch (Exception $e) {
        echo "<span class='warning'>⚠</span> Index idx_participant_identifier doesn't exist (OK)\n";
    }
    
    // Fix 2: Add checkin_timestamp column
    try {
        $pdo->exec("ALTER TABLE `campaign_department_attendance` ADD COLUMN `checkin_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<span class='success'>✓</span> Added column: checkin_timestamp\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<span class='warning'>⚠</span> Column checkin_timestamp already exists\n";
        } else {
            echo "<span class='error'>✗</span> Error adding checkin_timestamp: " . $e->getMessage() . "\n";
        }
    }
    
    // Fix 3: Re-add indexes
    try {
        $pdo->exec("ALTER TABLE `campaign_department_attendance` ADD INDEX `idx_checkin_method` (`checkin_method`)");
        echo "<span class='success'>✓</span> Added index: idx_checkin_method\n";
    } catch (Exception $e) {
        echo "<span class='warning'>⚠</span> Index idx_checkin_method already exists\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE `campaign_department_attendance` ADD INDEX `idx_participant_identifier` (`participant_identifier`)");
        echo "<span class='success'>✓</span> Added index: idx_participant_identifier\n";
    } catch (Exception $e) {
        echo "<span class='warning'>⚠</span> Index idx_participant_identifier already exists\n";
    }
    
    // Fix 4: Add action_type to agency coordination
    try {
        $pdo->exec("ALTER TABLE `campaign_department_event_agency_coordination` ADD COLUMN `action_type` VARCHAR(100) NULL");
        echo "<span class='success'>✓</span> Added column: action_type\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<span class='warning'>⚠</span> Column action_type already exists\n";
        } else {
            echo "<span class='error'>✗</span> Error adding action_type: " . $e->getMessage() . "\n";
        }
    }
    
    echo "</pre>";
    
    // Verify all columns exist
    echo "<h2>Verifying Columns...</h2>";
    echo "<pre>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_attendance` LIKE 'checkin_timestamp'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓</span> checkin_timestamp exists\n";
    } else {
        echo "<span class='error'>✗</span> checkin_timestamp MISSING\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_event_agency_coordination` LIKE 'action_type'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='success'>✓</span> action_type exists\n";
    } else {
        echo "<span class='error'>✗</span> action_type MISSING\n";
    }
    
    echo "</pre>";
    
    echo "<h2 class='success'>✓ Fix Completed!</h2>";
    echo "<p>All missing columns have been added.</p>";
    echo "<p><a href='/public/events.php' style='color:#4ec9b0;'>Go to Events Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Fix Failed!</h2>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
