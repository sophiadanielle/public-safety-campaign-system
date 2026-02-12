-- =====================================================
-- SMART MIGRATION: Only adds columns that don't exist
-- Run each ALTER statement SEPARATELY in phpMyAdmin
-- If you get an error, just skip to the next one
-- =====================================================

USE `LGU`;

-- Run this first - if it errors, skip it
ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

-- Run this second - if it errors, skip it
ALTER TABLE `campaign_department_events` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Run this third - if it errors, skip it
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;
