-- Migration Script: Copy all tables and data from `lgu` to `LGU`
-- This script migrates the entire database schema and data from lowercase `lgu` to uppercase `LGU`
-- Run this script as MySQL root user
-- Usage: mysql -u root -p < migrations/999_migrate_lgu_to_uppercase.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Step 1: Create LGU database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `LGU` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `LGU`;

-- Step 2: Get list of all base tables from lgu database
-- We'll process them one by one to preserve structure and data

-- Drop existing tables in LGU if they exist (to ensure clean migration)
-- Note: This will delete any existing data in LGU, so backup first if needed

-- Step 3: Copy all base tables from lgu to LGU
-- Using CREATE TABLE ... LIKE to preserve structure, then INSERT ... SELECT to copy data

-- Table: ai_model_versions
DROP TABLE IF EXISTS `LGU`.`ai_model_versions`;
CREATE TABLE `LGU`.`ai_model_versions` LIKE `lgu`.`ai_model_versions`;
INSERT INTO `LGU`.`ai_model_versions` SELECT * FROM `lgu`.`ai_model_versions`;

-- Table: ai_prediction_cache
DROP TABLE IF EXISTS `LGU`.`ai_prediction_cache`;
CREATE TABLE `LGU`.`ai_prediction_cache` LIKE `lgu`.`ai_prediction_cache`;
INSERT INTO `LGU`.`ai_prediction_cache` SELECT * FROM `lgu`.`ai_prediction_cache`;

-- Table: ai_prediction_requests
DROP TABLE IF EXISTS `LGU`.`ai_prediction_requests`;
CREATE TABLE `LGU`.`ai_prediction_requests` LIKE `lgu`.`ai_prediction_requests`;
INSERT INTO `LGU`.`ai_prediction_requests` SELECT * FROM `lgu`.`ai_prediction_requests`;

-- Table: ai_training_logs
DROP TABLE IF EXISTS `LGU`.`ai_training_logs`;
CREATE TABLE `LGU`.`ai_training_logs` LIKE `lgu`.`ai_training_logs`;
INSERT INTO `LGU`.`ai_training_logs` SELECT * FROM `lgu`.`ai_training_logs`;

-- Table: attachments
DROP TABLE IF EXISTS `LGU`.`attachments`;
CREATE TABLE `LGU`.`attachments` LIKE `lgu`.`attachments`;
INSERT INTO `LGU`.`attachments` SELECT * FROM `lgu`.`attachments`;

-- Table: attendance
DROP TABLE IF EXISTS `LGU`.`attendance`;
CREATE TABLE `LGU`.`attendance` LIKE `lgu`.`attendance`;
INSERT INTO `LGU`.`attendance` SELECT * FROM `lgu`.`attendance`;

-- Table: audience_members
DROP TABLE IF EXISTS `LGU`.`audience_members`;
CREATE TABLE `LGU`.`audience_members` LIKE `lgu`.`audience_members`;
INSERT INTO `LGU`.`audience_members` SELECT * FROM `lgu`.`audience_members`;

-- Table: audience_segments
DROP TABLE IF EXISTS `LGU`.`audience_segments`;
CREATE TABLE `LGU`.`audience_segments` LIKE `lgu`.`audience_segments`;
INSERT INTO `LGU`.`audience_segments` SELECT * FROM `lgu`.`audience_segments`;

-- Table: audit_logs
DROP TABLE IF EXISTS `LGU`.`audit_logs`;
CREATE TABLE `LGU`.`audit_logs` LIKE `lgu`.`audit_logs`;
INSERT INTO `LGU`.`audit_logs` SELECT * FROM `lgu`.`audit_logs`;

-- Table: automl_predictions
DROP TABLE IF EXISTS `LGU`.`automl_predictions`;
CREATE TABLE `LGU`.`automl_predictions` LIKE `lgu`.`automl_predictions`;
INSERT INTO `LGU`.`automl_predictions` SELECT * FROM `lgu`.`automl_predictions`;

-- Table: barangays
DROP TABLE IF EXISTS `LGU`.`barangays`;
CREATE TABLE `LGU`.`barangays` LIKE `lgu`.`barangays`;
INSERT INTO `LGU`.`barangays` SELECT * FROM `lgu`.`barangays`;

-- Table: campaigns
DROP TABLE IF EXISTS `LGU`.`campaigns`;
CREATE TABLE `LGU`.`campaigns` LIKE `lgu`.`campaigns`;
INSERT INTO `LGU`.`campaigns` SELECT * FROM `lgu`.`campaigns`;

-- Table: campaign_audience
DROP TABLE IF EXISTS `LGU`.`campaign_audience`;
CREATE TABLE `LGU`.`campaign_audience` LIKE `lgu`.`campaign_audience`;
INSERT INTO `LGU`.`campaign_audience` SELECT * FROM `lgu`.`campaign_audience`;

-- Table: campaign_content_items
DROP TABLE IF EXISTS `LGU`.`campaign_content_items`;
CREATE TABLE `LGU`.`campaign_content_items` LIKE `lgu`.`campaign_content_items`;
INSERT INTO `LGU`.`campaign_content_items` SELECT * FROM `lgu`.`campaign_content_items`;

-- Table: campaign_schedules
DROP TABLE IF EXISTS `LGU`.`campaign_schedules`;
CREATE TABLE `LGU`.`campaign_schedules` LIKE `lgu`.`campaign_schedules`;
INSERT INTO `LGU`.`campaign_schedules` SELECT * FROM `lgu`.`campaign_schedules`;

-- Table: content_items
DROP TABLE IF EXISTS `LGU`.`content_items`;
CREATE TABLE `LGU`.`content_items` LIKE `lgu`.`content_items`;
INSERT INTO `LGU`.`content_items` SELECT * FROM `lgu`.`content_items`;

-- Table: content_item_versions
DROP TABLE IF EXISTS `LGU`.`content_item_versions`;
CREATE TABLE `LGU`.`content_item_versions` LIKE `lgu`.`content_item_versions`;
INSERT INTO `LGU`.`content_item_versions` SELECT * FROM `lgu`.`content_item_versions`;

-- Table: content_tags
DROP TABLE IF EXISTS `LGU`.`content_tags`;
CREATE TABLE `LGU`.`content_tags` LIKE `lgu`.`content_tags`;
INSERT INTO `LGU`.`content_tags` SELECT * FROM `lgu`.`content_tags`;

-- Table: content_usage
DROP TABLE IF EXISTS `LGU`.`content_usage`;
CREATE TABLE `LGU`.`content_usage` LIKE `lgu`.`content_usage`;
INSERT INTO `LGU`.`content_usage` SELECT * FROM `lgu`.`content_usage`;

-- Table: conversations
DROP TABLE IF EXISTS `LGU`.`conversations`;
CREATE TABLE `LGU`.`conversations` LIKE `lgu`.`conversations`;
INSERT INTO `LGU`.`conversations` SELECT * FROM `lgu`.`conversations`;

-- Table: evaluation_reports
DROP TABLE IF EXISTS `LGU`.`evaluation_reports`;
CREATE TABLE `LGU`.`evaluation_reports` LIKE `lgu`.`evaluation_reports`;
INSERT INTO `LGU`.`evaluation_reports` SELECT * FROM `lgu`.`evaluation_reports`;

-- Table: events
DROP TABLE IF EXISTS `LGU`.`events`;
CREATE TABLE `LGU`.`events` LIKE `lgu`.`events`;
INSERT INTO `LGU`.`events` SELECT * FROM `lgu`.`events`;

-- Table: feedback
DROP TABLE IF EXISTS `LGU`.`feedback`;
CREATE TABLE `LGU`.`feedback` LIKE `lgu`.`feedback`;
INSERT INTO `LGU`.`feedback` SELECT * FROM `lgu`.`feedback`;

-- Table: impact_metrics
DROP TABLE IF EXISTS `LGU`.`impact_metrics`;
CREATE TABLE `LGU`.`impact_metrics` LIKE `lgu`.`impact_metrics`;
INSERT INTO `LGU`.`impact_metrics` SELECT * FROM `lgu`.`impact_metrics`;

-- Table: integration_logs
DROP TABLE IF EXISTS `LGU`.`integration_logs`;
CREATE TABLE `LGU`.`integration_logs` LIKE `lgu`.`integration_logs`;
INSERT INTO `LGU`.`integration_logs` SELECT * FROM `lgu`.`integration_logs`;

-- Table: messages
DROP TABLE IF EXISTS `LGU`.`messages`;
CREATE TABLE `LGU`.`messages` LIKE `lgu`.`messages`;
INSERT INTO `LGU`.`messages` SELECT * FROM `lgu`.`messages`;

-- Table: notification_logs
DROP TABLE IF EXISTS `LGU`.`notification_logs`;
CREATE TABLE `LGU`.`notification_logs` LIKE `lgu`.`notification_logs`;
INSERT INTO `LGU`.`notification_logs` SELECT * FROM `lgu`.`notification_logs`;

-- Table: notifications
DROP TABLE IF EXISTS `LGU`.`notifications`;
CREATE TABLE `LGU`.`notifications` LIKE `lgu`.`notifications`;
INSERT INTO `LGU`.`notifications` SELECT * FROM `lgu`.`notifications`;

-- Table: partners
DROP TABLE IF EXISTS `LGU`.`partners`;
CREATE TABLE `LGU`.`partners` LIKE `lgu`.`partners`;
INSERT INTO `LGU`.`partners` SELECT * FROM `lgu`.`partners`;

-- Table: partner_engagements
DROP TABLE IF EXISTS `LGU`.`partner_engagements`;
CREATE TABLE `LGU`.`partner_engagements` LIKE `lgu`.`partner_engagements`;
INSERT INTO `LGU`.`partner_engagements` SELECT * FROM `lgu`.`partner_engagements`;

-- Table: participation_history
DROP TABLE IF EXISTS `LGU`.`participation_history`;
CREATE TABLE `LGU`.`participation_history` LIKE `lgu`.`participation_history`;
INSERT INTO `LGU`.`participation_history` SELECT * FROM `lgu`.`participation_history`;

-- Table: permissions
DROP TABLE IF EXISTS `LGU`.`permissions`;
CREATE TABLE `LGU`.`permissions` LIKE `lgu`.`permissions`;
INSERT INTO `LGU`.`permissions` SELECT * FROM `lgu`.`permissions`;

-- Table: roles
DROP TABLE IF EXISTS `LGU`.`roles`;
CREATE TABLE `LGU`.`roles` LIKE `lgu`.`roles`;
INSERT INTO `LGU`.`roles` SELECT * FROM `lgu`.`roles`;

-- Table: role_permissions
DROP TABLE IF EXISTS `LGU`.`role_permissions`;
CREATE TABLE `LGU`.`role_permissions` LIKE `lgu`.`role_permissions`;
INSERT INTO `LGU`.`role_permissions` SELECT * FROM `lgu`.`role_permissions`;

-- Table: surveys
DROP TABLE IF EXISTS `LGU`.`surveys`;
CREATE TABLE `LGU`.`surveys` LIKE `lgu`.`surveys`;
INSERT INTO `LGU`.`surveys` SELECT * FROM `lgu`.`surveys`;

-- Table: survey_questions
DROP TABLE IF EXISTS `LGU`.`survey_questions`;
CREATE TABLE `LGU`.`survey_questions` LIKE `lgu`.`survey_questions`;
INSERT INTO `LGU`.`survey_questions` SELECT * FROM `lgu`.`survey_questions`;

-- Table: survey_responses
DROP TABLE IF EXISTS `LGU`.`survey_responses`;
CREATE TABLE `LGU`.`survey_responses` LIKE `lgu`.`survey_responses`;
INSERT INTO `LGU`.`survey_responses` SELECT * FROM `lgu`.`survey_responses`;

-- Table: tags
DROP TABLE IF EXISTS `LGU`.`tags`;
CREATE TABLE `LGU`.`tags` LIKE `lgu`.`tags`;
INSERT INTO `LGU`.`tags` SELECT * FROM `lgu`.`tags`;

-- Table: users
DROP TABLE IF EXISTS `LGU`.`users`;
CREATE TABLE `LGU`.`users` LIKE `lgu`.`users`;
INSERT INTO `LGU`.`users` SELECT * FROM `lgu`.`users`;

-- Step 4: Copy views from lgu to LGU
-- Note: Views need to be recreated with their definitions, and table references updated from lgu to LGU

-- View: campaign_engagement_summary
DROP VIEW IF EXISTS `LGU`.`campaign_engagement_summary`;
SET @view_def = (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'lgu' AND TABLE_NAME = 'campaign_engagement_summary');
-- Replace database references: `lgu`.`table` -> `LGU`.`table`
SET @view_def = REPLACE(@view_def, '`lgu`.', '`LGU`.');
SET @sql = CONCAT('CREATE VIEW `LGU`.`campaign_engagement_summary` AS ', @view_def);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- View: timing_effectiveness
DROP VIEW IF EXISTS `LGU`.`timing_effectiveness`;
SET @view_def = (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'lgu' AND TABLE_NAME = 'timing_effectiveness');
SET @view_def = REPLACE(@view_def, '`lgu`.', '`LGU`.');
SET @sql = CONCAT('CREATE VIEW `LGU`.`timing_effectiveness` AS ', @view_def);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- View: participation_history
DROP VIEW IF EXISTS `LGU`.`participation_history`;
SET @view_def = (SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'lgu' AND TABLE_NAME = 'participation_history');
SET @view_def = REPLACE(@view_def, '`lgu`.', '`LGU`.');
SET @sql = CONCAT('CREATE VIEW `LGU`.`participation_history` AS ', @view_def);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Restore foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 6: Verify migration
SELECT 
    'Migration Complete' AS status,
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'LGU' AND TABLE_TYPE = 'BASE TABLE') AS tables_in_lgu_uppercase,
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'lgu' AND TABLE_TYPE = 'BASE TABLE') AS tables_in_lgu_lowercase;

