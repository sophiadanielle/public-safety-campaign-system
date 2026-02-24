<?php
/**
 * Auto-migration script to add 'archived' to event status ENUM
 * This fixes the 502 error when loading archived events
 */

require_once __DIR__ . '/../../api/config.php';

header('Content-Type: application/json');

try {
    // Check current status column definition
    $checkStmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'status'");
    $column = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        $currentType = $column['Type'];
        echo json_encode([
            'status' => 'info',
            'message' => 'Current status definition',
            'current_type' => $currentType
        ], JSON_PRETTY_PRINT) . "\n";
        
        // Check if 'archived' is already in the ENUM
        if (strpos($currentType, 'archived') !== false) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Migration already applied - status ENUM already includes archived',
                'enum_values' => $currentType
            ], JSON_PRETTY_PRINT) . "\n";
            exit;
        }
    }
    
    // Apply the migration - add 'archived' to status ENUM
    $sql = "ALTER TABLE `campaign_department_events` 
            MODIFY COLUMN status ENUM('scheduled', 'ongoing', 'completed', 'cancelled', 'archived') 
            NOT NULL DEFAULT 'scheduled'";
    
    $pdo->exec($sql);
    
    // Verify the change
    $verifyStmt = $pdo->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'status'");
    $newColumn = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Migration 035 applied successfully',
        'new_type' => $newColumn['Type'],
        'note' => 'Archived events should now load without 502 errors'
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . "\n";
}
