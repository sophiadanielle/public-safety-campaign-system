<?php
/**
 * Auto-migration script to ensure audit_logs table exists and is properly structured
 * This fixes the "no audit logs found" issue
 */

require_once __DIR__ . '/../../api/config.php';

header('Content-Type: application/json');

try {
    $results = [];
    
    // Check if audit_logs table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'audit_logs'");
    
    if ($checkTable->rowCount() === 0) {
        // Create the audit_logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                action VARCHAR(150) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
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
        ");
        $results[] = 'Created audit_logs table';
    } else {
        // Check if details column exists
        $columns = $pdo->query("SHOW COLUMNS FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('details', $columns)) {
            $pdo->exec("ALTER TABLE audit_logs ADD COLUMN details TEXT NULL AFTER entity_id");
            $results[] = 'Added details column to audit_logs';
        }
        
        if (!in_array('metadata', $columns)) {
            $pdo->exec("ALTER TABLE audit_logs ADD COLUMN metadata JSON NULL AFTER details");
            $results[] = 'Added metadata column to audit_logs';
        }
    }
    
    // Check if campaign_department_audit_logs table exists (alternative name)
    $checkTable2 = $pdo->query("SHOW TABLES LIKE 'campaign_department_audit_logs'");
    
    if ($checkTable2->rowCount() === 0) {
        // Create the campaign_department_audit_logs table as well
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `campaign_department_audit_logs` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                action VARCHAR(150) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
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
        ");
        $results[] = 'Created campaign_department_audit_logs table';
    }
    
    // Count existing audit logs
    $countAuditLogs = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    $countCampaignAuditLogs = $pdo->query("SELECT COUNT(*) FROM campaign_department_audit_logs")->fetchColumn();
    
    $results[] = "audit_logs count: {$countAuditLogs}";
    $results[] = "campaign_department_audit_logs count: {$countCampaignAuditLogs}";
    
    // If both tables are empty, add a system initialization log entry
    if ($countAuditLogs == 0 && $countCampaignAuditLogs == 0) {
        $pdo->exec("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, created_at)
            VALUES (NULL, 'system_init', 'system', NULL, 'Audit logging system initialized', NOW())
        ");
        $results[] = 'Added system initialization audit log entry';
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Audit logs migration completed',
        'results' => $results
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . "\n";
}
