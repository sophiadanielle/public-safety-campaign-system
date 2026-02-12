<?php
/**
 * Run migration to fix agency coordination errors
 * Adds missing columns: action_type and post_event_notes
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=LGU', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Starting migration...\n\n";
    
    // 1. Add action_type to campaign_department_event_agency_coordination
    echo "1. Adding action_type column to agency_coordination table...\n";
    try {
        $pdo->exec("ALTER TABLE `campaign_department_event_agency_coordination` 
                    ADD COLUMN `action_type` VARCHAR(100) NULL AFTER `request_details`");
        echo "   ✓ action_type column added successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ action_type column already exists\n";
        } else {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Add post_event_notes to campaign_department_events
    echo "\n2. Adding post_event_notes column to events table...\n";
    try {
        $pdo->exec("ALTER TABLE `campaign_department_events` 
                    ADD COLUMN `post_event_notes` TEXT NULL");
        echo "   ✓ post_event_notes column added successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ post_event_notes column already exists\n";
        } else {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify columns exist
    echo "\n3. Verifying columns...\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_event_agency_coordination` LIKE 'action_type'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ action_type column verified in agency_coordination table\n";
    } else {
        echo "   ✗ action_type column NOT found\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'post_event_notes'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ post_event_notes column verified in events table\n";
    } else {
        echo "   ✗ post_event_notes column NOT found\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
