-- =====================================================
-- FINAL FIX - Add All Missing Columns
-- This script adds ALL missing columns with proper error handling
-- =====================================================

USE `LGU`;

-- Drop and recreate indexes to avoid duplicate key errors
ALTER TABLE `campaign_department_attendance` DROP INDEX IF EXISTS `idx_checkin_method`;
ALTER TABLE `campaign_department_attendance` DROP INDEX IF EXISTS `idx_participant_identifier`;

-- Add checkin_timestamp column (the one that's missing)
ALTER TABLE `campaign_department_attendance` 
ADD COLUMN IF NOT EXISTS `checkin_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Re-add indexes
ALTER TABLE `campaign_department_attendance` 
ADD INDEX IF NOT EXISTS `idx_checkin_method` (`checkin_method`);

ALTER TABLE `campaign_department_attendance` 
ADD INDEX IF NOT EXISTS `idx_participant_identifier` (`participant_identifier`);

-- Add action_type to agency coordination table
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN IF NOT EXISTS `action_type` VARCHAR(100) NULL;

SELECT 'All missing columns added successfully!' AS status;
