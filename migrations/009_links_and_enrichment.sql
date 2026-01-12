-- Add links and enrichment fields for cross-module wiring
USE `LGU`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Content usage can reference events and surveys
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_usage' AND COLUMN_NAME = 'event_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_usage` ADD COLUMN event_id INT UNSIGNED NULL AFTER tag_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_usage' AND COLUMN_NAME = 'survey_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_usage` ADD COLUMN survey_id INT UNSIGNED NULL AFTER event_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_usage' AND CONSTRAINT_NAME = 'fk_content_usage_event');
SET @sql = IF(@constraint_exists = 0, 'ALTER TABLE `campaign_department_content_usage` ADD CONSTRAINT fk_content_usage_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_usage' AND CONSTRAINT_NAME = 'fk_content_usage_survey');
SET @sql = IF(@constraint_exists = 0, 'ALTER TABLE `campaign_department_content_usage` ADD CONSTRAINT fk_content_usage_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Surveys can be tied to events
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_surveys' AND COLUMN_NAME = 'event_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_surveys` ADD COLUMN event_id INT UNSIGNED NULL AFTER campaign_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_surveys' AND CONSTRAINT_NAME = 'fk_surveys_event');
SET @sql = IF(@constraint_exists = 0, 'ALTER TABLE `campaign_department_surveys` ADD CONSTRAINT fk_surveys_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Partner engagements can target a specific event
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_partner_engagements' AND COLUMN_NAME = 'event_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_partner_engagements` ADD COLUMN event_id INT UNSIGNED NULL AFTER campaign_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_partner_engagements' AND CONSTRAINT_NAME = 'fk_partner_engagements_event');
SET @sql = IF(@constraint_exists = 0, 'ALTER TABLE `campaign_department_partner_engagements` ADD CONSTRAINT fk_partner_engagements_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Audience enrichment
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'demographics_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN demographics_json JSON NULL AFTER criteria', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'risk_level');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN risk_level VARCHAR(50) NULL AFTER demographics_json', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'geographies_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN geographies_json JSON NULL AFTER risk_level', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'preferences_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN preferences_json JSON NULL AFTER geographies_json', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_members' AND COLUMN_NAME = 'risk_level');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_members` ADD COLUMN risk_level VARCHAR(50) NULL AFTER channel', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_members' AND COLUMN_NAME = 'geo');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_members` ADD COLUMN geo VARCHAR(150) NULL AFTER risk_level', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_members' AND COLUMN_NAME = 'preferences_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_members` ADD COLUMN preferences_json JSON NULL AFTER geo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Event logistics/materials
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'logistics_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN logistics_json JSON NULL AFTER ends_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'materials_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN materials_json JSON NULL AFTER logistics_json', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

