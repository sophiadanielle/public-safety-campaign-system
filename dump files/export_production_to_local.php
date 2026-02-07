<?php
/**
 * Export Production Database and Import to Local
 * 
 * This script exports the production LGU database from alertaraqc.com
 * and provides instructions to import it into your local lgu database.
 * 
 * This script does NOT modify .env - it connects directly to production.
 * 
 * Usage:
 * php export_production_to_local.php
 * 
 * This will create: production_lgu_export.sql
 * Import that file into your local lgu database via phpMyAdmin or MySQL CLI
 */

declare(strict_types=1);

// Production database credentials (hardcoded - does not use .env)
$prodHost = 'alertaraqc.com';
$prodName = 'LGU';
$prodUser = 'root';
$prodPass = 'YsqnXk6q#145';
$prodPort = 3306;

// Connect directly to production database
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $prodHost, $prodPort, $prodName);
    $pdo = new PDO($dsn, $prodUser, $prodPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Connected to production database at $prodHost\n";
} catch (PDOException $e) {
    die("ERROR: Cannot connect to production database: " . $e->getMessage() . "\n");
}

echo "=== Production Database Export Tool ===\n\n";
echo "Source: Production database at alertaraqc.com\n";
echo "Target: Local SQL export file (production_lgu_export.sql)\n\n";

// Get database name from connection
$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
echo "Connected to database: $dbName\n\n";

// Get all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tables) . " tables\n\n";

// Start SQL export
$exportFile = __DIR__ . '/production_lgu_export.sql';
$handle = fopen($exportFile, 'w');

if (!$handle) {
    die("ERROR: Cannot create export file: $exportFile\n");
}

// Write header
fwrite($handle, "-- Production Database Export\n");
fwrite($handle, "-- Source: Production LGU database at alertaraqc.com\n");
fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Database: $dbName\n\n");
fwrite($handle, "SET NAMES utf8mb4;\n");
fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

// Export each table
$exportedTables = 0;
$exportedRows = 0;

foreach ($tables as $table) {
    echo "Exporting table: $table... ";
    
    try {
        // Get table structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        if (!$createTable) {
            echo "SKIPPED (no structure)\n";
            continue;
        }
        
        // Write DROP and CREATE statements
        fwrite($handle, "-- Table: $table\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $createTable['Create Table'] . ";\n\n");
        
        // Get row count
        $rowCount = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        
        if ($rowCount > 0) {
            // Export data
            fwrite($handle, "-- Data for table: $table ($rowCount rows)\n");
            $stmt = $pdo->query("SELECT * FROM `$table`");
            
            $firstRow = true;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($firstRow) {
                    $columns = array_keys($row);
                    fwrite($handle, "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n");
                    $firstRow = false;
                } else {
                    fwrite($handle, ",\n");
                }
                
                // Escape values
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($value);
                }, array_values($row));
                
                fwrite($handle, "(" . implode(', ', $values) . ")");
            }
            
            if (!$firstRow) {
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
        fwrite($handle, "-- ERROR exporting table $table: " . $e->getMessage() . "\n\n");
    }
}

// Write footer
fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($handle);

echo "\n=== Export Complete ===\n";
echo "Exported tables: $exportedTables\n";
echo "Exported rows: $exportedRows\n";
echo "Export file: $exportFile\n";
echo "\n=== Next Steps ===\n";
echo "1. Import the SQL file into your local database:\n";
echo "   - Via phpMyAdmin: Select 'lgu' database → Import → Choose 'production_lgu_export.sql'\n";
echo "   - Via MySQL CLI: mysql -u root -p lgu < production_lgu_export.sql\n";
echo "\n2. After import, your local 'lgu' database will match production 'LGU' database\n";

