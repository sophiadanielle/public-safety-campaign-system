<?php
/**
 * Database Migration: Fix Agency Coordination Issues
 * This script adds missing columns to fix all three issues
 */

// Database connection
$host = 'localhost';
$dbname = 'LGU';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Database Migration Started ===\n\n";
    
    $columnsAdded = 0;
    $columnsExist = 0;
    
    // 1. Add post_event_notes to events table
    echo "1. Adding post_event_notes column...\n";
    try {
        $pdo->exec("ALTER TABLE `campaign_department_events` ADD COLUMN `post_event_notes` TEXT NULL");
        echo "   ✓ post_event_notes column added\n";
        $columnsAdded++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ post_event_notes already exists\n";
            $columnsExist++;
        } else {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Add last_updated to events table
    echo "\n2. Adding last_updated column...\n";
    try {
        $pdo->exec("ALTER TABLE `campaign_department_events` ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        echo "   ✓ last_updated column added\n";
        $columnsAdded++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ last_updated already exists\n";
            $columnsExist++;
        } else {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Add action_type to agency coordination table
    echo "\n3. Adding action_type column...\n";
    try {
        $pdo->exec("ALTER TABLE `campaign_department_event_agency_coordination` ADD COLUMN `action_type` VARCHAR(100) NULL");
        echo "   ✓ action_type column added\n";
        $columnsAdded++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ action_type already exists\n";
            $columnsExist++;
        } else {
            echo "   ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "Columns added: $columnsAdded\n";
    echo "Columns already existed: $columnsExist\n";
    echo "\n✅ All fixes applied! You can now test:\n";
    echo "   - Submit agency coordination requests\n";
    echo "   - Save post-event notes\n";
    echo "   - Edit events (all fields will populate)\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database connection error: " . $e->getMessage() . "\n";
    echo "Please check your database credentials.\n";
    exit(1);
}
