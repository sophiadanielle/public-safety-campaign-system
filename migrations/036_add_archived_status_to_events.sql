-- Migration: Add 'archived' status to events table
-- This ensures the archived status is available for event archiving functionality

-- Check and modify the status column to include 'archived'
ALTER TABLE `campaign_department_events` 
MODIFY COLUMN `status` ENUM('scheduled','ongoing','completed','cancelled','archived') NOT NULL DEFAULT 'scheduled';
