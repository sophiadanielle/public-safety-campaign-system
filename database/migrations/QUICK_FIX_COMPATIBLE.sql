-- =====================================================
-- QUICK FIX: Agency Coordination Errors
-- Compatible with MySQL 5.7+ (no IF NOT EXISTS)
-- Run this in phpMyAdmin - ignore duplicate column errors
-- =====================================================

USE `LGU`;

-- Add action_type column
-- If you get "Duplicate column" error, that's OK - it means the column already exists
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL AFTER `request_details`;

-- Add post_event_notes column
-- If you get "Duplicate column" error, that's OK - it means the column already exists
ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

-- Verify the columns were added
SELECT 'Migration Complete - Verifying columns...' AS status;
SHOW COLUMNS FROM `campaign_department_event_agency_coordination` LIKE 'action_type';
SHOW COLUMNS FROM `campaign_department_events` LIKE 'post_event_notes';
