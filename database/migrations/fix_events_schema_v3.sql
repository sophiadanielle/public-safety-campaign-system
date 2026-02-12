-- =====================================================
-- Event Management System - Database Migration Script V3
-- Purpose: Add missing tables and columns for full functionality
-- Date: February 12, 2026
-- Fixed: Simplified approach - just run statements and ignore errors
-- =====================================================

-- Use the correct database
USE `LGU`;

-- =====================================================
-- 1. CREATE NEW TABLES
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
