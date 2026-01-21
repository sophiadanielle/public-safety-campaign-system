<?php
/**
 * Complete Backup of campaign_db Database (contains all system data)
 * 
 * This script creates a complete backup of the campaign_db database which contains
 * all the submodules and features of the system.
 * 
 * Usage: php backup_campaign_db.php
 * 
 * Output: campaign_db_backup_complete_YYYYMMDD_HHMMSS.sql
 */

declare(strict_types=1);

// Local database credentials
$localHost = 'localhost';
$dbName = 'campaign_db'; // This is where the actual system data is
$localUser = 'root';
$localPort = 3306;
$passwords = ['Phiarren@182212', ''];

$pdo = null;
$connected = false;

foreach ($passwords as $localPass) {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $localHost, $localPort, $dbName);
        $pdo = new PDO($dsn, $localUser, $localPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName' AND TABLE_TYPE = 'BASE TABLE'")->fetchColumn();
        
        echo "✓ Connected to database: $dbName ($tableCount tables found)\n";
        $connected = true;
        break;
    } catch (PDOException $e) {
        continue;
    }
}

if (!$connected) {
    die("ERROR: Cannot connect to database: $dbName\n");
}

// Create backup filename with timestamp
$timestamp = date('Ymd_His');
$backupFile = __DIR__ . "/campaign_db_backup_complete_$timestamp.sql";
$handle = fopen($backupFile, 'w');

if (!$handle) {
    die("ERROR: Cannot create backup file: $backupFile\n");
}

echo "Creating backup: $backupFile\n\n";

// Write header
fwrite($handle, "-- Complete Backup of campaign_db Database\n");
fwrite($handle, "-- This database contains all submodules and features of the system\n");
fwrite($handle, "-- Database: $dbName\n");
fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Host: $localHost:$localPort\n\n");
fwrite($handle, "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
fwrite($handle, "USE `$dbName`;\n\n");
fwrite($handle, "SET NAMES utf8mb4;\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
fwrite($handle, "SET AUTOCOMMIT = 0;\n");
fwrite($handle, "START TRANSACTION;\n\n");

// Get all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tables) . " tables\n\n";

$exportedTables = 0;
$exportedRows = 0;
$errors = [];

// Export each table
foreach ($tables as $table) {
    echo "Exporting table: $table... ";
    
    try {
        // Get table structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        if (!$createTable) {
            echo "SKIPPED (no structure)\n";
            $errors[] = "Table $table: Could not get structure";
            continue;
        }
        
        // Write DROP and CREATE statements
        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- Table structure for table `$table`\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $createTable['Create Table'] . ";\n\n");
        
        // Get row count
        $rowCount = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        
        if ($rowCount > 0) {
            // Export data in batches for large tables
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Dumping data for table `$table` ($rowCount rows)\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            
            $batchSize = 1000;
            $offset = 0;
            $firstBatch = true;
            
            while ($offset < $rowCount) {
                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT $batchSize OFFSET $offset");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($rows)) break;
                
                if ($firstBatch) {
                    $columns = array_keys($rows[0]);
                    $columnList = '`' . implode('`, `', $columns) . '`';
                    fwrite($handle, "INSERT INTO `$table` ($columnList) VALUES\n");
                    $firstBatch = false;
                }
                
                $firstRow = true;
                foreach ($rows as $row) {
                    if (!$firstRow || $offset > 0) {
                        fwrite($handle, ",\n");
                    }
                    
                    // Escape values properly
                    $values = array_map(function($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($value);
                    }, array_values($row));
                    
                    fwrite($handle, "(" . implode(', ', $values) . ")");
                    $firstRow = false;
                }
                
                $offset += $batchSize;
            }
            
            fwrite($handle, ";\n\n");
            
            $exportedRows += $rowCount;
            echo "OK ($rowCount rows)\n";
        } else {
            echo "OK (0 rows, structure only)\n";
        }
        
        $exportedTables++;
        
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $errors[] = "Table $table: " . $e->getMessage();
        fwrite($handle, "-- ERROR exporting table $table: " . $e->getMessage() . "\n\n");
    }
}

// Export stored procedures, functions, triggers, events
echo "\nExporting stored procedures, functions, triggers, events...\n";

try {
    // Stored Procedures
    $procedures = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = '$dbName'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($procedures)) {
        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- Stored Procedures\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");
        foreach ($procedures as $proc) {
            $procName = $proc['Name'];
            $createProc = $pdo->query("SHOW CREATE PROCEDURE `$procName`")->fetch(PDO::FETCH_ASSOC);
            if ($createProc) {
                fwrite($handle, "DROP PROCEDURE IF EXISTS `$procName`;\n");
                fwrite($handle, $createProc['Create Procedure'] . ";\n\n");
            }
        }
        echo "  - Exported " . count($procedures) . " stored procedures\n";
    }
    
    // Functions
    $functions = $pdo->query("SHOW FUNCTION STATUS WHERE Db = '$dbName'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($functions)) {
        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- Functions\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");
        foreach ($functions as $func) {
            $funcName = $func['Name'];
            $createFunc = $pdo->query("SHOW CREATE FUNCTION `$funcName`")->fetch(PDO::FETCH_ASSOC);
            if ($createFunc) {
                fwrite($handle, "DROP FUNCTION IF EXISTS `$funcName`;\n");
                fwrite($handle, $createFunc['Create Function'] . ";\n\n");
            }
        }
        echo "  - Exported " . count($functions) . " functions\n";
    }
    
    // Triggers
    $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($triggers)) {
        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- Triggers\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");
        foreach ($triggers as $trigger) {
            $triggerName = $trigger['Trigger'];
            $createTrigger = $pdo->query("SHOW CREATE TRIGGER `$triggerName`")->fetch(PDO::FETCH_ASSOC);
            if ($createTrigger) {
                fwrite($handle, "DROP TRIGGER IF EXISTS `$triggerName`;\n");
                fwrite($handle, $createTrigger['SQL Original Statement'] . ";\n\n");
            }
        }
        echo "  - Exported " . count($triggers) . " triggers\n";
    }
    
    // Events
    $events = $pdo->query("SHOW EVENTS")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($events)) {
        fwrite($handle, "-- --------------------------------------------------------\n");
        fwrite($handle, "-- Events\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");
        foreach ($events as $event) {
            $eventName = $event['Name'];
            $createEvent = $pdo->query("SHOW CREATE EVENT `$eventName`")->fetch(PDO::FETCH_ASSOC);
            if ($createEvent) {
                fwrite($handle, "DROP EVENT IF EXISTS `$eventName`;\n");
                fwrite($handle, $createEvent['Create Event'] . ";\n\n");
            }
        }
        echo "  - Exported " . count($events) . " events\n";
    }
    
    if (empty($procedures) && empty($functions) && empty($triggers) && empty($events)) {
        echo "  - No stored procedures, functions, triggers, or events found\n";
    }
} catch (PDOException $e) {
    echo "Note: Could not export procedures/functions/triggers/events: " . $e->getMessage() . "\n";
}

// Write footer
fwrite($handle, "\n-- --------------------------------------------------------\n");
fwrite($handle, "-- Commit transaction\n");
fwrite($handle, "-- --------------------------------------------------------\n\n");
fwrite($handle, "COMMIT;\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
fwrite($handle, "SET AUTOCOMMIT = 1;\n\n");
fwrite($handle, "-- Backup completed: " . date('Y-m-d H:i:s') . "\n");
fclose($handle);

// Summary
echo "\n=== Backup Complete ===\n";
echo "Database: $dbName\n";
echo "File: $backupFile\n";
echo "Tables exported: $exportedTables\n";
echo "Total rows exported: $exportedRows\n";

if (!empty($errors)) {
    echo "\nWarnings/Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

$fileSize = filesize($backupFile);
echo "File size: " . number_format($fileSize / 1024, 2) . " KB (" . number_format($fileSize / 1024 / 1024, 2) . " MB)\n";

echo "\n✓ Backup file is ready!\n";
echo "\nNote: Your 'lgu' database is currently empty.\n";
echo "This backup is from 'campaign_db' which contains all your system data.\n";
echo "You can import this backup into 'lgu' if needed.\n";




