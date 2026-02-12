-- =====================================================
-- FINAL FIX: All Agency Coordination & Events Errors
-- Run each statement ONE AT A TIME in phpMyAdmin
-- If you get "Duplicate column" error, skip that statement
-- =====================================================

USE `LGU`;

-- Statement 1: Add post_event_notes (for Save Notes feature)
ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

-- Statement 2: Add last_updated (for Update Events feature)
ALTER TABLE `campaign_department_events` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Statement 3: Add action_type (for Agency Coordination Submit)
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;

-- =====================================================
-- VERIFICATION - Run this to check all columns exist
-- =====================================================

SELECT 'Checking Events Table Columns:' AS info;
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'LGU' 
  AND TABLE_NAME = 'campaign_department_events' 
  AND COLUMN_NAME IN ('post_event_notes', 'last_updated');

SELECT 'Checking Agency Coordination Table Columns:' AS info;
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'LGU' 
  AND TABLE_NAME = 'campaign_department_event_agency_coordination' 
  AND COLUMN_NAME = 'action_type';
