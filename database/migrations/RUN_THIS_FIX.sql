-- =====================================================
-- COMPLETE FIX FOR ALL AGENCY COORDINATION ISSUES
-- Run this in phpMyAdmin SQL tab
-- Instructions: Copy ALL of this and paste into SQL tab, then click Go
-- =====================================================

USE `LGU`;

-- Add post_event_notes column to events table
ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

-- Add last_updated column to events table  
ALTER TABLE `campaign_department_events` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Add action_type column to agency coordination table
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;

-- Success message
SELECT 'All columns added successfully! You can now test the features.' AS Result;
