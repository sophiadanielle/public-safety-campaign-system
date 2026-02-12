-- =====================================================
-- Event Management System - Database Migration Script
-- Purpose: Add missing tables and columns for full functionality
-- Date: February 12, 2026
-- =====================================================

-- Use the correct database
USE `LGU`;

-- =====================================================
-- 1. ADD MISSING COLUMNS TO campaign_department_events
-- =====================================================

-- Check and add missing columns to events table
ALTER TABLE `campaign_department_events`
ADD COLUMN IF NOT EXISTS `hazard_focus` VARCHAR(255) NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `target_audience_profile_id` INT UNSIGNED NULL AFTER `hazard_focus`,
ADD COLUMN IF NOT EXISTS `transport_requirements` TEXT NULL AFTER `ends_at`,
ADD COLUMN IF NOT EXISTS `trainer_requirements` TEXT NULL AFTER `transport_requirements`,
ADD COLUMN IF NOT EXISTS `equipment_requirements` TEXT NULL AFTER `trainer_requirements`,
ADD COLUMN IF NOT EXISTS `volunteer_requirements` TEXT NULL AFTER `equipment_requirements`,
ADD COLUMN IF NOT EXISTS `attendance_count` INT UNSIGNED DEFAULT 0 AFTER `volunteer_requirements`,
ADD COLUMN IF NOT EXISTS `created_by` INT UNSIGNED NULL AFTER `attendance_count`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add foreign key for target_audience_profile_id if it doesn't exist
ALTER TABLE `campaign_department_events`
ADD CONSTRAINT `fk_events_audience_segment` 
FOREIGN KEY (`target_audience_profile_id`) 
REFERENCES `campaign_department_audience_segments`(`id`) 
ON DELETE SET NULL;

-- Add foreign key for created_by if it doesn't exist
ALTER TABLE `campaign_department_events`
ADD CONSTRAINT `fk_events_created_by` 
FOREIGN KEY (`created_by`) 
REFERENCES `campaign_department_users`(`id`) 
ON DELETE SET NULL;

-- =====================================================
-- 2. CREATE campaign_department_event_facilitators TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `campaign_department_event_facilitators` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `role` VARCHAR(100) NULL DEFAULT 'facilitator',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_facilitator` (`event_id`, `user_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_facilitators_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_facilitators_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `campaign_department_users`(`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CREATE campaign_department_event_audience_segments TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `campaign_department_event_audience_segments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `segment_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_segment` (`event_id`, `segment_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_segment_id` (`segment_id`),
  CONSTRAINT `fk_event_segments_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_event_segments_segment` 
    FOREIGN KEY (`segment_id`) 
    REFERENCES `campaign_department_audience_segments`(`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CREATE campaign_department_event_agency_coordination TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `campaign_department_event_agency_coordination` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `agency_type` ENUM('police', 'fire', 'medical', 'rescue', 'other') NOT NULL,
  `agency_name` VARCHAR(255) NOT NULL,
  `contact_person` VARCHAR(255) NULL,
  `contact_number` VARCHAR(50) NULL,
  `request_details` TEXT NULL,
  `status` ENUM('pending', 'confirmed', 'declined', 'completed') DEFAULT 'pending',
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_agency_type` (`agency_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_agency_coord_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_agency_coord_created_by` 
    FOREIGN KEY (`created_by`) 
    REFERENCES `campaign_department_users`(`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. ADD MISSING COLUMN TO campaign_department_attendance
-- =====================================================

-- Add participant_identifier column if it doesn't exist
ALTER TABLE `campaign_department_attendance`
ADD COLUMN IF NOT EXISTS `participant_identifier` VARCHAR(255) NULL AFTER `event_id`,
ADD COLUMN IF NOT EXISTS `checkin_method` ENUM('QR', 'manual', 'online') DEFAULT 'manual' AFTER `participant_identifier`,
ADD COLUMN IF NOT EXISTS `checkin_notes` TEXT NULL AFTER `checkin_method`;

-- Add index for faster lookups
ALTER TABLE `campaign_department_attendance`
ADD INDEX IF NOT EXISTS `idx_participant_identifier` (`participant_identifier`),
ADD INDEX IF NOT EXISTS `idx_checkin_method` (`checkin_method`);

-- =====================================================
-- 6. CREATE campaign_department_event_conflicts TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `campaign_department_event_conflicts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `conflicting_event_id` INT UNSIGNED NOT NULL,
  `conflict_type` ENUM('venue', 'time', 'resource', 'other') NOT NULL,
  `severity` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `resolved` BOOLEAN DEFAULT FALSE,
  `resolution_notes` TEXT NULL,
  `detected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_conflicting_event_id` (`conflicting_event_id`),
  KEY `idx_resolved` (`resolved`),
  CONSTRAINT `fk_conflicts_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_conflicts_conflicting_event` 
    FOREIGN KEY (`conflicting_event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. CREATE campaign_department_event_audit_log TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `campaign_department_event_audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_audit_user` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `campaign_department_users`(`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. CREATE campaign_department_event_integration_checkpoints TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS `campaign_department_event_integration_checkpoints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `checkpoint_type` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `data` JSON NULL,
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_checkpoint_type` (`checkpoint_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_checkpoints_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `campaign_department_events`(`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. ADD INDEXES FOR BETTER PERFORMANCE
-- =====================================================

-- Add indexes to events table for common queries
ALTER TABLE `campaign_department_events`
ADD INDEX IF NOT EXISTS `idx_event_date` (`event_date`),
ADD INDEX IF NOT EXISTS `idx_event_time` (`event_time`),
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_event_type` (`event_type`),
ADD INDEX IF NOT EXISTS `idx_campaign_id` (`campaign_id`),
ADD INDEX IF NOT EXISTS `idx_linked_campaign_id` (`linked_campaign_id`),
ADD INDEX IF NOT EXISTS `idx_created_by` (`created_by`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'Migration completed successfully!' AS status;
SELECT 'All missing tables and columns have been added.' AS message;
SELECT 'You can now use all event management features.' AS next_step;
