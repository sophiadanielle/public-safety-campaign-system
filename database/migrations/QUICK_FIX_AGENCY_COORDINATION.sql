-- =====================================================
-- QUICK FIX: Agency Coordination Errors
-- Run this in phpMyAdmin or MySQL client
-- =====================================================

USE `LGU`;

-- Add action_type column (if it doesn't exist)
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN IF NOT EXISTS `action_type` VARCHAR(100) NULL AFTER `request_details`;

-- Add post_event_notes column (if it doesn't exist)
ALTER TABLE `campaign_department_events` 
ADD COLUMN IF NOT EXISTS `post_event_notes` TEXT NULL;

-- Verify the columns were added
SELECT 'Verification Results:' AS status;
SHOW COLUMNS FROM `campaign_department_event_agency_coordination` LIKE 'action_type';
SHOW COLUMNS FROM `campaign_department_events` LIKE 'post_event_notes';
