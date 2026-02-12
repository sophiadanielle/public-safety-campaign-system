<?php
/**
 * Production Database Migration
 * Run this on the production server to fix all agency coordination issues
 * Usage: php production_fix.php
 */

// Production database credentials
$host = 'localhost';
$dbname = 'LGU';
$username = 'root';

// Get password from command line argument or prompt
if (isset($argv[1])) {
    $password = $argv[1];
} else {
    echo "Enter MySQL root password: ";
    $password = trim(fgets(STDIN));
}

echo "\n=== Production Database Migration ===\n";
echo "Database: $dbname\n";
echo "Host: $host\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database\n\n";
    
    $success = 0;
    $skipped = 0;
    $errors = 0;
    
    // Column definitions
    $columns = [
        [
            'table' => 'campaign_department_events',
            'column' => 'post_event_notes',
            'definition' => 'TEXT NULL',
            'description' => 'Post-event notes column'
        ],
        [
            'table' => 'campaign_department_events',
            'column' => 'last_updated',
            'definition' => 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
            'description' => 'Last updated timestamp'
        ],
        [
            'table' => 'campaign_department_event_agency_coordination',
            'column' => 'action_type',
            'definition' => 'VARCHAR(100) NULL',
            'description' => 'Action type for agency coordination'
        ]
    ];
    
    foreach ($columns as $index => $col) {
        $num = $index + 1;
        echo "$num. Adding {$col['description']}...\n";
        echo "   Table: {$col['table']}\n";
        echo "   Column: {$col['column']}\n";
        
        // Check if column exists
        $checkSql = "SELECT COUNT(*) as count 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = :dbname 
                     AND TABLE_NAME = :table 
                     AND COLUMN_NAME = :column";
        
        try {
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute([
                'dbname' => $dbname,
                'table' => $col['table'],
                'column' => $col['column']
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo "   ℹ Column already exists - skipping\n\n";
                $skipped++;
                continue;
            }
        } catch (PDOException $e) {
            // If INFORMATION_SCHEMA check fails, try to add anyway
            echo "   ⚠ Could not check if column exists, attempting to add...\n";
        }
        
        // Add the column
        $alterSql = "ALTER TABLE `{$col['table']}` ADD COLUMN `{$col['column']}` {$col['definition']}";
        
        try {
            $pdo->exec($alterSql);
            echo "   ✓ Column added successfully\n\n";
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "   ℹ Column already exists\n\n";
                $skipped++;
            } else {
                echo "   ✗ Error: " . $e->getMessage() . "\n\n";
                $errors++;
            }
        }
    }
    
    echo "=== Migration Summary ===\n";
    echo "✓ Successfully added: $success\n";
    echo "ℹ Already existed: $skipped\n";
    echo "✗ Errors: $errors\n\n";
    
    if ($errors === 0) {
        echo "✅ Migration completed successfully!\n";
        echo "\nYou can now test:\n";
        echo "  - Submit agency coordination requests\n";
        echo "  - Save post-event notes\n";
        echo "  - Edit events (all fields will populate)\n";
    } else {
        echo "⚠ Migration completed with errors. Please check the output above.\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "  1. Database credentials are correct\n";
    echo "  2. MySQL server is running\n";
    echo "  3. Database 'LGU' exists\n";
    exit(1);
}
