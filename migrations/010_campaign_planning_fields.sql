-- Add planning and resource allocation fields to campaigns table
USE `LGU`;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'objectives');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN objectives TEXT NULL AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'location');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN location VARCHAR(255) NULL AFTER objectives', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'assigned_staff');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN assigned_staff JSON NULL AFTER location', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'barangay_target_zones');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN barangay_target_zones JSON NULL AFTER assigned_staff', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'budget');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN budget DECIMAL(12,2) NULL AFTER barangay_target_zones', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'staff_count');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN staff_count INT UNSIGNED NULL AFTER budget', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'materials_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN materials_json JSON NULL AFTER staff_count', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Update status enum to include new statuses
ALTER TABLE `campaign_department_campaigns`
MODIFY COLUMN status ENUM('draft','pending','approved','ongoing','completed','scheduled','active','archived') NOT NULL DEFAULT 'draft';

-- Update existing status values if needed (optional, adjust as needed)
-- UPDATE campaigns SET status = 'draft' WHERE status = 'draft';
-- UPDATE campaigns SET status = 'ongoing' WHERE status = 'active';
-- UPDATE campaigns SET status = 'completed' WHERE status = 'completed';

