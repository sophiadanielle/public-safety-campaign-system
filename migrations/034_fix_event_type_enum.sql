-- Migration 034: Fix event_type ENUM to include all valid types
-- This fixes the "Data truncated for column 'event_type'" error

ALTER TABLE `campaign_department_events` 
MODIFY COLUMN event_type ENUM('seminar', 'drill', 'workshop', 'orientation', 'meeting', 'other') NOT NULL DEFAULT 'seminar';
