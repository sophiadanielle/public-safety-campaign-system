<?php

namespace App\Controllers;

use PDO;

class AuditController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get consolidated audit logs from all sources
     */
    public function index(?array $user, array $params = []): array
    {
        $limit = min((int) ($_GET['limit'] ?? 500), 1000);
        $offset = (int) ($_GET['offset'] ?? 0);
        
        $allLogs = [];
        
        // 1. Get general audit logs (campaigns, content) - try both table names
        $auditTableNames = ['campaign_department_audit_logs', 'audit_logs'];
        $auditTableFound = null;
        
        foreach ($auditTableNames as $tableName) {
            try {
                $checkTable = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
                if ($checkTable->rowCount() > 0) {
                    $auditTableFound = $tableName;
                    break;
                }
            } catch (\PDOException $e) {
                continue;
            }
        }
        
        if ($auditTableFound) {
            try {
                // Check table structure to determine column names
                $columnsResult = $this->pdo->query("DESCRIBE `{$auditTableFound}`");
                $columns = $columnsResult->fetchAll(PDO::FETCH_COLUMN);
                
                $hasDetails = in_array('details', $columns);
                $hasMetadata = in_array('metadata', $columns);
                $detailsColumn = $hasDetails ? 'al.details' : ($hasMetadata ? 'al.metadata as details' : "'' as details");
                
                // Check which user name column exists in users table
                $userColumns = $this->pdo->query("SHOW COLUMNS FROM campaign_department_users")->fetchAll(PDO::FETCH_COLUMN);
                $userNameColumn = in_array('full_name', $userColumns) ? 'u.full_name' : (in_array('name', $userColumns) ? 'u.name' : "'System'");
                
                $stmt = $this->pdo->prepare("
                    SELECT 
                        al.id,
                        al.user_id,
                        {$userNameColumn} as user_name,
                        r.name as user_role,
                        al.action,
                        al.entity_type,
                        al.entity_id,
                        {$detailsColumn},
                        al.created_at
                    FROM `{$auditTableFound}` al
                    LEFT JOIN campaign_department_users u ON al.user_id = u.id
                    LEFT JOIN campaign_department_roles r ON u.role_id = r.id
                    ORDER BY al.created_at DESC
                    LIMIT :limit OFFSET :offset
                ");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $details = $row['details'] ?? '';
                    // Try to decode JSON metadata for better display
                    if ($details && is_string($details)) {
                        $decoded = json_decode($details, true);
                        if ($decoded && is_array($decoded)) {
                            $details = implode(', ', array_map(function($k, $v) {
                                return ucfirst($k) . ': ' . (is_array($v) ? json_encode($v) : $v);
                            }, array_keys($decoded), array_values($decoded)));
                        }
                    }
                    
                    $allLogs[] = [
                        'id' => 'general_' . $row['id'],
                        'user_id' => $row['user_id'],
                        'user_name' => $row['user_name'] ?? 'System',
                        'user_role' => $row['user_role'] ?? 'N/A',
                        'action' => $row['action'],
                        'entity_type' => $row['entity_type'],
                        'entity_id' => $row['entity_id'],
                        'details' => $details,
                        'created_at' => $row['created_at'],
                        'source' => 'general'
                    ];
                }
            } catch (\PDOException $e) {
                error_log('AuditController: Failed to fetch general audit logs: ' . $e->getMessage());
            }
        }
        
        // 2. Get event audit logs
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_event_audit_log'");
            if ($checkTable->rowCount() > 0) {
                // Check which user name column exists
                $userColumns = $this->pdo->query("SHOW COLUMNS FROM campaign_department_users")->fetchAll(PDO::FETCH_COLUMN);
                $userNameColumn = in_array('full_name', $userColumns) ? 'u.full_name' : (in_array('name', $userColumns) ? 'u.name' : "'System'");
                
                $stmt = $this->pdo->prepare("
                    SELECT 
                        eal.id,
                        eal.event_id as entity_id,
                        eal.user_id,
                        {$userNameColumn} as user_name,
                        r.name as user_role,
                        eal.action_type as action,
                        eal.field_name,
                        eal.old_value,
                        eal.new_value,
                        eal.change_details as details,
                        eal.created_at
                    FROM campaign_department_event_audit_log eal
                    LEFT JOIN campaign_department_users u ON eal.user_id = u.id
                    LEFT JOIN campaign_department_roles r ON u.role_id = r.id
                    ORDER BY eal.created_at DESC
                    LIMIT :limit OFFSET :offset
                ");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $details = $row['details'];
                    if ($row['field_name'] && $row['old_value'] !== null) {
                        $details = "Changed {$row['field_name']}: {$row['old_value']} → {$row['new_value']}";
                    }
                    
                    $allLogs[] = [
                        'id' => 'event_' . $row['id'],
                        'user_id' => $row['user_id'],
                        'user_name' => $row['user_name'] ?? 'System',
                        'user_role' => $row['user_role'] ?? 'N/A',
                        'action' => $row['action'],
                        'entity_type' => 'event',
                        'entity_id' => $row['entity_id'],
                        'details' => $details,
                        'created_at' => $row['created_at'],
                        'source' => 'events'
                    ];
                }
            }
        } catch (\PDOException $e) {
            error_log('AuditController: Failed to fetch event audit logs: ' . $e->getMessage());
        }
        
        // 3. Get survey audit logs
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_survey_audit_log'");
            if ($checkTable->rowCount() > 0) {
                // Check which user name column exists
                $userColumns = $this->pdo->query("SHOW COLUMNS FROM campaign_department_users")->fetchAll(PDO::FETCH_COLUMN);
                $userNameColumn = in_array('full_name', $userColumns) ? 'u.full_name' : (in_array('name', $userColumns) ? 'u.name' : "'System'");
                
                $stmt = $this->pdo->prepare("
                    SELECT 
                        sal.id,
                        sal.survey_id as entity_id,
                        sal.user_id,
                        {$userNameColumn} as user_name,
                        r.name as user_role,
                        sal.action_type as action,
                        sal.field_name,
                        sal.old_value,
                        sal.new_value,
                        sal.change_details as details,
                        sal.created_at
                    FROM campaign_department_survey_audit_log sal
                    LEFT JOIN campaign_department_users u ON sal.user_id = u.id
                    LEFT JOIN campaign_department_roles r ON u.role_id = r.id
                    ORDER BY sal.created_at DESC
                    LIMIT :limit OFFSET :offset
                ");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $details = $row['details'];
                    if ($row['field_name'] && $row['old_value'] !== null) {
                        $details = "Changed {$row['field_name']}: {$row['old_value']} → {$row['new_value']}";
                    }
                    
                    $allLogs[] = [
                        'id' => 'survey_' . $row['id'],
                        'user_id' => $row['user_id'],
                        'user_name' => $row['user_name'] ?? 'System',
                        'user_role' => $row['user_role'] ?? 'N/A',
                        'action' => $row['action'],
                        'entity_type' => 'survey',
                        'entity_id' => $row['entity_id'],
                        'details' => $details,
                        'created_at' => $row['created_at'],
                        'source' => 'surveys'
                    ];
                }
            }
        } catch (\PDOException $e) {
            error_log('AuditController: Failed to fetch survey audit logs: ' . $e->getMessage());
        }
        
        // Sort all logs by created_at descending
        usort($allLogs, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Limit total results
        $allLogs = array_slice($allLogs, 0, $limit);
        
        return [
            'success' => true,
            'data' => $allLogs,
            'total' => count($allLogs)
        ];
    }
}
