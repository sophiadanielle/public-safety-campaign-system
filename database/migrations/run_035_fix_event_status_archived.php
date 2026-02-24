<?php
/**
 * Auto-migration script to add 'archived' to event status ENUM
 * This fixes the 502 error when loading archived events
 */

require_once __DIR__ . '/../../api/config.php';

header('Content-Type: application/json');

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed',
        'error' => $mysqli->connect_error
    ], JSON_PRETTY_PRINT) . "\n";
    exit;
}

try {
    // Check current status column definition
    $result = $mysqli->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'status'");
    $column = $result ? $result->fetch_assoc() : null;
    
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
    
    if (!$mysqli->query($sql)) {
        throw new Exception($mysqli->error);
    }
    
    // Verify the change
    $verifyResult = $mysqli->query("SHOW COLUMNS FROM `campaign_department_events` LIKE 'status'");
    $newColumn = $verifyResult ? $verifyResult->fetch_assoc() : null;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Migration 035 applied successfully',
        'new_type' => $newColumn['Type'],
        'note' => 'Archived events should now load without 502 errors'
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . "\n";
}
