-- Migration: Create campaign_department_ai_recommendations table
-- Stores compiled AI campaign recommendations from crime + disaster reports

CREATE TABLE IF NOT EXISTS `campaign_department_ai_recommendations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` ENUM('crime', 'disaster') NOT NULL,
    `campaign_title` VARCHAR(255) NOT NULL,
    `campaign_description` TEXT,
    `incident_category` VARCHAR(255) NOT NULL,
    `report_count` INT NOT NULL DEFAULT 0,
    `priority_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `priority_level` VARCHAR(20) NOT NULL DEFAULT 'Low',
    `source_report_ids` JSON,
    `generated_by` VARCHAR(50) NOT NULL DEFAULT 'fallback',
    `recommendation_hash` VARCHAR(64) NOT NULL,
    `data_snapshot` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_priority` (`priority_level`),
    INDEX `idx_hash` (`recommendation_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
