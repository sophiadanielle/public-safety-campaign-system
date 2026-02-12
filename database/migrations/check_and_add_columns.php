<?php
/**
 * Smart Migration: Checks if columns exist before adding them
 * This prevents "Duplicate column" errors
 */

$host = 'localhost';
$dbname = 'LGU';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: $dbname\n\n";
    
    // Define columns to add
    $columns = [
        [
            'table' => 'campaign_department_events',
            'column' => 'post_event_notes',
            'sql' => "ALTER TABLE `campaign_department_events` ADD COLUMN `post_event_notes` TEXT NULL"
        ],
        [
            'table' => 'campaign_department_events',
            'column' => 'last_updated',
            'sql' => "ALTER TABLE `campaign_department_events` ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP"
        ],
        [
            'table' => 'campaign_department_event_agency_coordination',
            'column' => 'action_type',
            'sql' => "ALTER TABLE `campaign_department_event_agency_coordination` ADD COLUMN `action_type` VARCHAR(100) NULL"
        ]
    ];
    
    $added = 0;
    $skipped = 0;
    
    foreach ($columns as $col) {
        echo "Checking {$col['table']}.{$col['column']}... ";
        
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$col['table']}` LIKE :column");
        $stmt->execute(['column' => $col['column']]);
        
        if ($stmt->rowCount() > 0) {
            echo "EXISTS (skipping)\n";
            $skipped++;
        } else {
            echo "MISSING (adding)... ";
            try {
                $pdo->exec($col['sql']);
                echo "SUCCESS\n";
                $added++;
            } catch (PDOException $e) {
                echo "ERROR: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Added: $added\n";
    echo "Skipped: $skipped\n";
    echo "\nAll done! Test your features now.\n";
    
} catch (PDOException $e) {
    echo "Connection error: " . $e->getMessage() . "\n";
    exit(1);
}
