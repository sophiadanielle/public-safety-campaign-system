-- Migration: Fix status ENUM to include all required values
-- The ENUM currently only has 'cancelled' and 'archived' but is missing other statuses
-- This migration ensures all status values are present

ALTER TABLE `campaign_department_events` 
MODIFY COLUMN `status` ENUM('scheduled','ongoing','completed','cancelled','archived') NOT NULL DEFAULT 'scheduled';
