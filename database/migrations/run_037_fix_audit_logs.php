<?php
/**
 * Auto-migration script to ensure campaign_department_audit_logs table is properly structured
 * This fixes the "no audit logs found" issue
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
    $results = [];
    
    // Check if campaign_department_audit_logs table exists
    $checkTable = $mysqli->query("SHOW TABLES LIKE 'campaign_department_audit_logs'");
    
    if ($checkTable && $checkTable->num_rows === 0) {
        // Create the campaign_department_audit_logs table
        if (!$mysqli->query("
            CREATE TABLE IF NOT EXISTS `campaign_department_audit_logs` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                action VARCHAR(150) NOT NULL,
                entity_type VARCHAR(50) NULL,
                entity_id INT UNSIGNED NULL,
                details TEXT NULL,
                metadata JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ")) {
            throw new Exception($mysqli->error);
        }
        $results[] = 'Created campaign_department_audit_logs table';
    } else {
        // Table exists, check for missing columns
        $result = $mysqli->query("SHOW COLUMNS FROM campaign_department_audit_logs");
        $columns = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
        }
        
        // Add missing columns one by one
        if (!in_array('details', $columns)) {
            if (!$mysqli->query("ALTER TABLE campaign_department_audit_logs ADD COLUMN details TEXT NULL")) {
                throw new Exception($mysqli->error);
            }
            $results[] = 'Added details column';
        }
        
        if (!in_array('metadata', $columns)) {
            if (!$mysqli->query("ALTER TABLE campaign_department_audit_logs ADD COLUMN metadata JSON NULL")) {
                throw new Exception($mysqli->error);
            }
            $results[] = 'Added metadata column';
        }
        
        // Check and add indexes if they don't exist
        $indexResult = $mysqli->query("SHOW INDEX FROM campaign_department_audit_logs");
        $existingIndexes = [];
        if ($indexResult) {
            while ($row = $indexResult->fetch_assoc()) {
                $existingIndexes[] = $row['Key_name'];
            }
        }
        
        if (!in_array('idx_user_id', $existingIndexes)) {
            $mysqli->query("ALTER TABLE campaign_department_audit_logs ADD INDEX idx_user_id (user_id)");
            $results[] = 'Added idx_user_id index';
        }
        
        if (!in_array('idx_entity', $existingIndexes)) {
            $mysqli->query("ALTER TABLE campaign_department_audit_logs ADD INDEX idx_entity (entity_type, entity_id)");
            $results[] = 'Added idx_entity index';
        }
        
        if (!in_array('idx_created_at', $existingIndexes)) {
            $mysqli->query("ALTER TABLE campaign_department_audit_logs ADD INDEX idx_created_at (created_at)");
            $results[] = 'Added idx_created_at index';
        }
        
        $results[] = 'Updated campaign_department_audit_logs table structure';
    }
    
    // Count existing audit logs
    $result1 = $mysqli->query("SELECT COUNT(*) as count FROM campaign_department_audit_logs");
    $countAuditLogs = $result1 ? $result1->fetch_assoc()['count'] : 0;
    
    $results[] = "campaign_department_audit_logs count: {$countAuditLogs}";
    
    // If table is empty, add a system initialization log entry
    if ($countAuditLogs == 0) {
        if (!$mysqli->query("
            INSERT INTO campaign_department_audit_logs (user_id, action, entity_type, entity_id, details, created_at)
            VALUES (NULL, 'system_init', 'system', NULL, 'Audit logging system initialized', NOW())
        ")) {
            throw new Exception($mysqli->error);
        }
        $results[] = 'Added system initialization audit log entry';
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Audit logs migration completed',
        'results' => $results
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . "\n";
}
