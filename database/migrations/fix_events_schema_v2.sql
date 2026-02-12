-- =====================================================
-- Event Management System - Database Migration Script V2
-- Purpose: Add missing tables and columns for full functionality
-- Date: February 12, 2026
-- Fixed: Removed IF NOT EXISTS from ALTER TABLE statements
-- =====================================================

-- Use the correct database
USE `LGU`;

-- =====================================================
-- 1. CREATE NEW TABLES FIRST
-- =====================================================

-- Create event facilitators table
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

-- Create event audience segments table
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

-- Create agency coordination table
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
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create event conflicts table
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

-- Create audit log table
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
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create integration checkpoints table
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
-- 2. ADD COLUMNS TO EXISTING TABLES (One at a time to avoid errors)
-- =====================================================

-- Add columns to campaign_department_events (ignore errors if column exists)
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'hazard_focus') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `hazard_focus` VARCHAR(255) NULL AFTER `description`',
    'SELECT "Column hazard_focus already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'target_audience_profile_id') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `target_audience_profile_id` INT UNSIGNED NULL',
    'SELECT "Column target_audience_profile_id already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'transport_requirements') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `transport_requirements` TEXT NULL',
    'SELECT "Column transport_requirements already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'trainer_requirements') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `trainer_requirements` TEXT NULL',
    'SELECT "Column trainer_requirements already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'equipment_requirements') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `equipment_requirements` TEXT NULL',
    'SELECT "Column equipment_requirements already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'volunteer_requirements') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `volunteer_requirements` TEXT NULL',
    'SELECT "Column volunteer_requirements already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'attendance_count') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `attendance_count` INT UNSIGNED DEFAULT 0',
    'SELECT "Column attendance_count already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'created_by') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `created_by` INT UNSIGNED NULL',
    'SELECT "Column created_by already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE `campaign_department_events` ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "Column updated_at already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add columns to campaign_department_attendance
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_attendance' AND COLUMN_NAME = 'participant_identifier') = 0,
    'ALTER TABLE `campaign_department_attendance` ADD COLUMN `participant_identifier` VARCHAR(255) NULL',
    'SELECT "Column participant_identifier already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_attendance' AND COLUMN_NAME = 'checkin_method') = 0,
    'ALTER TABLE `campaign_department_attendance` ADD COLUMN `checkin_method` ENUM(\'QR\', \'manual\', \'online\') DEFAULT \'manual\'',
    'SELECT "Column checkin_method already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'LGU' AND TABLE_NAME = 'campaign_department_attendance' AND COLUMN_NAME = 'checkin_notes') = 0,
    'ALTER TABLE `campaign_department_attendance` ADD COLUMN `checkin_notes` TEXT NULL',
    'SELECT "Column checkin_notes already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. ADD INDEXES FOR PERFORMANCE
-- =====================================================

-- Add indexes (ignore errors if they exist)
ALTER TABLE `campaign_department_events` ADD INDEX `idx_event_date` (`event_date`);
ALTER TABLE `campaign_department_events` ADD INDEX `idx_event_time` (`event_time`);
ALTER TABLE `campaign_department_events` ADD INDEX `idx_status` (`status`);
ALTER TABLE `campaign_department_events` ADD INDEX `idx_event_type` (`event_type`);
ALTER TABLE `campaign_department_events` ADD INDEX `idx_campaign_id` (`campaign_id`);
ALTER TABLE `campaign_department_events` ADD INDEX `idx_linked_campaign_id` (`linked_campaign_id`);

ALTER TABLE `campaign_department_attendance` ADD INDEX `idx_participant_identifier` (`participant_identifier`);
ALTER TABLE `campaign_department_attendance` ADD INDEX `idx_checkin_method` (`checkin_method`);

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'Migration completed successfully!' AS status;
