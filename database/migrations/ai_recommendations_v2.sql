-- Migration v2: Rebuild campaign_department_ai_recommendations with full schema
-- Drops old table and recreates with trend_key, scoring breakdown, AI fields

DROP TABLE IF EXISTS `campaign_department_ai_recommendations`;

CREATE TABLE `campaign_department_ai_recommendations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` ENUM('crime', 'disaster') NOT NULL,
    `campaign_title` VARCHAR(255) NOT NULL,
    `main_trend` VARCHAR(255) NOT NULL,
    `trend_key` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `report_count` INT NOT NULL DEFAULT 0,
    `cluster_report_ids` JSON,
    `affected_locations` JSON,
    `earliest_date` DATETIME DEFAULT NULL,
    `latest_date` DATETIME DEFAULT NULL,
    `severity_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `frequency_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `recency_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `geographic_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `priority_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `priority_level` VARCHAR(20) NOT NULL DEFAULT 'Low',
    `scoring_breakdown` JSON,
    `ai_reasoning` TEXT,
    `ai_recommended_actions` JSON,
    `ai_target_audience` VARCHAR(500) DEFAULT NULL,
    `generated_by` VARCHAR(50) NOT NULL DEFAULT 'rule-based',
    `recommendation_hash` VARCHAR(64) NOT NULL,
    `data_snapshot` JSON,
    `is_test_data` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_trend_key` (`trend_key`),
    INDEX `idx_category` (`category`),
    INDEX `idx_priority` (`priority_level`),
    INDEX `idx_hash` (`recommendation_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
