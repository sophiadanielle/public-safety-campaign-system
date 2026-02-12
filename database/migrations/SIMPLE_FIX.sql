-- =====================================================
-- SIMPLE FIX: Add Missing Columns Only
-- Run this in phpMyAdmin - no verification queries
-- =====================================================

USE `LGU`;

ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

ALTER TABLE `campaign_department_events` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;
