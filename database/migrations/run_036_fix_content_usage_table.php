<?php
/**
 * Auto-migration script to ensure content_usage table has all required columns
 * This fixes the campaign not being populated in content usage records
 */

require_once __DIR__ . '/../../api/config.php';

header('Content-Type: application/json');

try {
    // Check if table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'campaign_department_content_usage'");
    
    if ($checkTable->rowCount() === 0) {
        // Create the table with all required columns
        $pdo->exec("
            CREATE TABLE `campaign_department_content_usage` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                content_item_id INT NOT NULL,
                campaign_id INT NULL,
                event_id INT NULL,
                survey_id INT NULL,
                tag_id INT NULL,
                usage_context VARCHAR(255) NULL,
                is_archived TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_content_item (content_item_id),
                INDEX idx_campaign (campaign_id),
                INDEX idx_event (event_id),
                INDEX idx_is_archived (is_archived)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Created campaign_department_content_usage table with all columns'
        ], JSON_PRETTY_PRINT) . "\n";
        exit;
    }
    
    // Table exists, check for missing columns
    $columns = $pdo->query("SHOW COLUMNS FROM campaign_department_content_usage")->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [];
    
    if (!in_array('campaign_id', $columns)) {
        $columnsToAdd[] = "ADD COLUMN campaign_id INT NULL AFTER content_item_id";
        $columnsToAdd[] = "ADD INDEX idx_campaign (campaign_id)";
    }
    
    if (!in_array('event_id', $columns)) {
        $columnsToAdd[] = "ADD COLUMN event_id INT NULL AFTER campaign_id";
        $columnsToAdd[] = "ADD INDEX idx_event (event_id)";
    }
    
    if (!in_array('survey_id', $columns)) {
        $columnsToAdd[] = "ADD COLUMN survey_id INT NULL AFTER event_id";
    }
    
    if (!in_array('is_archived', $columns)) {
        $columnsToAdd[] = "ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0";
        $columnsToAdd[] = "ADD INDEX idx_is_archived (is_archived)";
    }
    
    if (!in_array('updated_at', $columns)) {
        $columnsToAdd[] = "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    }
    
    if (count($columnsToAdd) > 0) {
        $sql = "ALTER TABLE campaign_department_content_usage " . implode(", ", $columnsToAdd);
        $pdo->exec($sql);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Added missing columns to campaign_department_content_usage',
            'columns_added' => $columnsToAdd
        ], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'All required columns already exist in campaign_department_content_usage',
            'existing_columns' => $columns
        ], JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT) . "\n";
}
