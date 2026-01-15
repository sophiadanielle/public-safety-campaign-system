<?php
/**
 * Complete Backup of Local lgu Database
 * 
 * This script creates a complete backup of the local lgu database including:
 * - All table structures
 * - All data
 * - Stored procedures, functions, triggers, events
 * 
 * It will try both 'lgu' and 'LGU' databases to find the one with data.
 * 
 * Usage: php backup_local_lgu_complete.php
 * 
 * Output: lgu_backup_complete_YYYYMMDD_HHMMSS.sql
 */

declare(strict_types=1);

// Local database credentials
$localHost = 'localhost';
$localUser = 'root';
$localPort = 3306;
$passwords = ['Phiarren@182212', ''];

// Try to find which database has tables
$databases = ['lgu', 'LGU'];
$pdo = null;
$dbName = null;
$connected = false;

foreach ($databases as $db) {
    foreach ($passwords as $localPass) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $localHost, $localPort, $db);
            $pdo = new PDO($dsn, $localUser, $localPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // Check if database has tables
            $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_TYPE = 'BASE TABLE'")->fetchColumn();
            
            if ($tableCount > 0) {
                $dbName = $db;
                echo "✓ Connected to database: $dbName ($tableCount tables found)\n";
                $connected = true;
                break 2;
            }
        } catch (PDOException $e) {
            continue;
        }
    }
}

if (!$connected || !$dbName) {
    // Try to connect without database to check what exists
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $localHost, $localPort);
        $pdo = new PDO($dsn, $localUser, $passwords[0], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Available databases: " . implode(', ', $dbs) . "\n";
        
        foreach ($dbs as $db) {
            if (strtolower($db) === 'lgu') {
                $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_TYPE = 'BASE TABLE'")->fetchColumn();
                echo "Database '$db' has $tableCount tables\n";
            }
        }
    } catch (PDOException $e) {
        // Ignore
    }
    
    die("ERROR: Cannot find a database with tables. Both 'lgu' and 'LGU' appear to be empty.\n");
}

// Create backup filename with timestamp
$timestamp = date('Ymd_His');
$backupFile = __DIR__ . "/lgu_backup_complete_$timestamp.sql";
$handle = fopen($backupFile, 'w');

if (!$handle) {
    die("ERROR: Cannot create backup file: $backupFile\n");
}

echo "Creating backup: $backupFile\n\n";

// Write header
fwrite($handle, "-- Complete Backup of Local Database\n");
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
            // Export data
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Dumping data for table `$table` ($rowCount rows)\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                fwrite($handle, "INSERT INTO `$table` ($columnList) VALUES\n");
                
                $firstRow = true;
                foreach ($rows as $row) {
                    if (!$firstRow) {
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
                
                fwrite($handle, ";\n\n");
            }
            
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
    }
    
    echo "✓ Stored procedures, functions, triggers, events exported\n";
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
echo "File size: " . number_format($fileSize / 1024, 2) . " KB\n";

if ($exportedTables > 0) {
    echo "\n✓ Backup file is ready for import\n";
} else {
    echo "\n⚠ WARNING: Backup created but database is empty (0 tables)\n";
    echo "You may need to import data from production first.\n";
}

