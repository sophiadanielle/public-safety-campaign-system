<?php
/**
 * Auto-migration script for 034_fix_event_type_enum.sql
 * This script automatically updates the event_type ENUM to include all valid types
 * Run this file directly via browser or CLI to apply the migration
 */

require_once __DIR__ . '/../../api/config.php';

header('Content-Type: application/json');

try {
    // Check if migration has already been run
    $checkStmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'event_type'");
    $column = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        $currentType = $column['Type'];
        echo json_encode([
            'status' => 'info',
            'message' => 'Current event_type definition',
            'current_type' => $currentType
        ], JSON_PRETTY_PRINT) . "\n";
        
        // Check if 'meeting' and 'other' are already in the ENUM
        if (strpos($currentType, 'meeting') !== false && strpos($currentType, 'other') !== false) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Migration already applied - event_type ENUM already includes all types',
                'enum_values' => $currentType
            ], JSON_PRETTY_PRINT) . "\n";
            exit;
        }
    }
    
    // Apply the migration
    $sql = "ALTER TABLE `campaign_department_events` 
            MODIFY COLUMN event_type ENUM('seminar', 'drill', 'workshop', 'orientation', 'meeting', 'other') 
            NOT NULL DEFAULT 'seminar'";
    
    $pdo->exec($sql);
    
    // Verify the change
    $verifyStmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'event_type'");
    $newColumn = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Migration 034 applied successfully',
        'new_type' => $newColumn['Type'],
        'note' => 'Event creation with Orientation, Meeting, and Other types should now work'
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . "\n";
}
