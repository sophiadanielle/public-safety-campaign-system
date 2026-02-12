-- =====================================================
-- FIX ALL MISSING COLUMNS
-- Run this in phpMyAdmin SQL tab
-- =====================================================

USE `LGU`;

-- 1. Add post_event_notes to events table (if missing)
ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

-- 2. Add last_updated to events table (if missing)
ALTER TABLE `campaign_department_events` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- 3. Verify columns exist
SELECT 'Checking columns...' AS status;
SHOW COLUMNS FROM `campaign_department_events` WHERE Field IN ('post_event_notes', 'last_updated');
SHOW COLUMNS FROM `campaign_department_event_agency_coordination` WHERE Field = 'action_type';
