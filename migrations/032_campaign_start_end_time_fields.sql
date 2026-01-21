-- Add start_time and end_time fields to campaigns table
-- This enables proper scheduling with time precision
USE `LGU`;

-- Add start_time column after start_date
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'start_time');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN start_time TIME NULL AFTER start_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add end_time column after end_date
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'end_time');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN end_time TIME NULL AFTER end_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


