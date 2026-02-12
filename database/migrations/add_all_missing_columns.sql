-- =====================================================
-- Add All Missing Columns - Comprehensive Migration
-- Purpose: Add all missing columns causing errors
-- Date: February 12, 2026
-- =====================================================

USE `LGU`;

-- =====================================================
-- 1. ADD COLUMNS TO campaign_department_attendance
-- =====================================================

-- Add participant_identifier if it doesn't exist
ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `participant_identifier` VARCHAR(255) NULL;

-- Add checkin_method if it doesn't exist
ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `checkin_method` ENUM('QR', 'manual', 'online') DEFAULT 'manual';

-- Add checkin_notes if it doesn't exist
ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `checkin_notes` TEXT NULL;

-- Add checkin_timestamp if it doesn't exist
ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `checkin_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add indexes
ALTER TABLE `campaign_department_attendance` 
ADD INDEX `idx_participant_identifier` (`participant_identifier`);

ALTER TABLE `campaign_department_attendance` 
ADD INDEX `idx_checkin_method` (`checkin_method`);

-- =====================================================
-- 2. ADD COLUMNS TO campaign_department_event_agency_coordination
-- =====================================================

-- Add action_type if it doesn't exist (for tracking what action was taken)
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'Migration completed successfully!' AS status;
SELECT 'Added missing columns to attendance and agency_coordination tables' AS message;
