-- =====================================================
-- MANUAL FIX: Run each statement ONE AT A TIME
-- Copy one line, paste in phpMyAdmin SQL tab, click Go
-- If you get "Duplicate column" error, that's OK - skip to next line
-- =====================================================

USE `LGU`;

-- Statement 1: Copy this line, paste in SQL tab, click Go
ALTER TABLE `campaign_department_events` ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Statement 2: Copy this line, paste in SQL tab, click Go
ALTER TABLE `campaign_department_event_agency_coordination` ADD COLUMN `action_type` VARCHAR(100) NULL;

-- That's it! Only 2 columns need to be added.
-- post_event_notes already exists in your database.
