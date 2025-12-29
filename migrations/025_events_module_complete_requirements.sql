-- Event & Seminar Management Module - Complete Requirements Implementation
-- Migration 025: Full event module with all required fields and coordination
-- System: Public Safety Campaign Management Scheduler for Barangays in Quezon City
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. UPDATE EVENTS TABLE WITH ALL REQUIRED FIELDS
-- ============================================

-- Ensure event_name exists (rename from name if needed)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'event_name');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE events CHANGE COLUMN name event_name VARCHAR(200) NOT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure linked_campaign_id exists (rename from campaign_id if needed)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'linked_campaign_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE events CHANGE COLUMN campaign_id linked_campaign_id INT UNSIGNED NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add event_title (alias for event_name, but keep both for compatibility)
ALTER TABLE events ADD COLUMN IF NOT EXISTS event_title VARCHAR(200) NULL AFTER id;
UPDATE events SET event_title = event_name WHERE event_title IS NULL AND event_name IS NOT NULL;

-- Update event_type enum to match requirements
ALTER TABLE events MODIFY COLUMN event_type ENUM('seminar', 'drill', 'workshop', 'orientation') NOT NULL DEFAULT 'seminar';

-- Add all required fields
ALTER TABLE events 
    ADD COLUMN IF NOT EXISTS event_description TEXT NULL AFTER event_type,
    ADD COLUMN IF NOT EXISTS hazard_focus VARCHAR(255) NULL AFTER event_description,
    ADD COLUMN IF NOT EXISTS target_audience_profile_id INT UNSIGNED NULL AFTER hazard_focus,
    ADD COLUMN IF NOT EXISTS date DATE NULL AFTER target_audience_profile_id,
    ADD COLUMN IF NOT EXISTS start_time TIME NULL AFTER date,
    ADD COLUMN IF NOT EXISTS end_time TIME NULL AFTER start_time,
    ADD COLUMN IF NOT EXISTS venue VARCHAR(255) NULL AFTER location,
    ADD COLUMN IF NOT EXISTS event_status ENUM('draft', 'scheduled', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'draft' AFTER venue,
    ADD COLUMN IF NOT EXISTS transport_requirements TEXT NULL AFTER event_status,
    ADD COLUMN IF NOT EXISTS trainer_requirements TEXT NULL AFTER transport_requirements,
    ADD COLUMN IF NOT EXISTS equipment_requirements TEXT NULL AFTER trainer_requirements,
    ADD COLUMN IF NOT EXISTS volunteer_requirements TEXT NULL AFTER equipment_requirements,
    ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER volunteer_requirements,
    ADD COLUMN IF NOT EXISTS last_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_by,
    ADD COLUMN IF NOT EXISTS attendance_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_updated,
    ADD COLUMN IF NOT EXISTS post_event_notes TEXT NULL AFTER attendance_count,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER post_event_notes;

-- Add foreign key for target_audience_profile_id (references audience_segments)
ALTER TABLE events 
    ADD CONSTRAINT IF NOT EXISTS fk_events_target_audience 
    FOREIGN KEY (target_audience_profile_id) REFERENCES audience_segments(id) ON DELETE SET NULL;

-- Add foreign key for created_by
ALTER TABLE events 
    ADD CONSTRAINT IF NOT EXISTS fk_events_created_by 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================
-- 2. CREATE AGENCY COORDINATION TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS event_agency_coordination (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    agency_type ENUM('police', 'fire_rescue', 'traffic', 'emergency_response', 'community_policing', 'other') NOT NULL,
    agency_name VARCHAR(255) NOT NULL,
    request_status ENUM('requested', 'confirmed', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'requested',
    request_details TEXT NULL,
    confirmation_details TEXT NULL,
    fulfillment_details TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    fulfilled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_agency_coordination_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_agency_event (event_id),
    INDEX idx_event_agency_type (agency_type),
    INDEX idx_event_agency_status (request_status)
) ENGINE=InnoDB;

-- ============================================
-- 3. CREATE EVENT CONFLICTS TRACKING TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS event_conflicts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    conflict_type ENUM('venue', 'date_time', 'resource') NOT NULL,
    conflicting_event_id INT UNSIGNED NULL,
    conflict_details TEXT NULL,
    resolved BOOLEAN NOT NULL DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_conflicts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_conflicts_conflicting FOREIGN KEY (conflicting_event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_event_conflicts_event (event_id),
    INDEX idx_event_conflicts_resolved (resolved)
) ENGINE=InnoDB;

-- ============================================
-- 4. CREATE EVENT AUDIT LOG TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS event_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action_type ENUM('created', 'updated', 'status_changed', 'resource_added', 'agency_coordinated', 'cancelled', 'deleted') NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    change_details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_audit_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_audit_event (event_id),
    INDEX idx_event_audit_user (user_id),
    INDEX idx_event_audit_action (action_type),
    INDEX idx_event_audit_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- 5. CREATE INTEGRATION CHECKPOINTS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS event_integration_checkpoints (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    subsystem_type ENUM('law_enforcement', 'traffic_transport', 'fire_rescue', 'emergency_response', 'community_policing', 'target_audience') NOT NULL,
    integration_status ENUM('pending', 'sent', 'acknowledged', 'confirmed', 'failed') NOT NULL DEFAULT 'pending',
    sent_data JSON NULL,
    received_data JSON NULL,
    last_sync_at TIMESTAMP NULL,
    sync_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_integration_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_integration_event (event_id),
    INDEX idx_event_integration_subsystem (subsystem_type),
    INDEX idx_event_integration_status (integration_status)
) ENGINE=InnoDB;

-- ============================================
-- 6. UPDATE EXISTING DATA
-- ============================================

-- Set event_title from event_name if not set
UPDATE events SET event_title = event_name WHERE event_title IS NULL AND event_name IS NOT NULL;

-- Derive date and times from starts_at/ends_at if not set
UPDATE events 
SET date = DATE(starts_at), 
    start_time = TIME(starts_at),
    end_time = TIME(ends_at)
WHERE (date IS NULL OR start_time IS NULL) AND starts_at IS NOT NULL;

-- Set default event_status to 'draft' if NULL
UPDATE events SET event_status = 'draft' WHERE event_status IS NULL;

-- ============================================
-- 7. ADD INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX IF NOT EXISTS idx_events_date ON events(date);
CREATE INDEX IF NOT EXISTS idx_events_event_status ON events(event_status);
CREATE INDEX IF NOT EXISTS idx_events_event_type ON events(event_type);
CREATE INDEX IF NOT EXISTS idx_events_venue ON events(venue);
CREATE INDEX IF NOT EXISTS idx_events_linked_campaign ON events(linked_campaign_id);
CREATE INDEX IF NOT EXISTS idx_events_target_audience ON events(target_audience_profile_id);
CREATE INDEX IF NOT EXISTS idx_events_created_by ON events(created_by);
CREATE INDEX IF NOT EXISTS idx_events_date_time_venue ON events(date, start_time, venue);

-- ============================================
-- 8. CREATE VIEW FOR EVENT SUMMARY
-- ============================================

CREATE OR REPLACE VIEW event_summary_view AS
SELECT 
    e.id as event_id,
    e.event_title,
    e.event_name,
    e.event_type,
    e.event_status,
    e.date,
    e.start_time,
    e.end_time,
    e.venue,
    e.linked_campaign_id,
    e.target_audience_profile_id,
    e.hazard_focus,
    e.attendance_count,
    e.created_by,
    e.created_at,
    e.updated_at,
    COUNT(DISTINCT a.id) as total_attendance,
    COUNT(DISTINCT ef.user_id) as facilitator_count,
    COUNT(DISTINCT eas.segment_id) as audience_segment_count,
    COUNT(DISTINCT ep.partner_id) as partner_count,
    COUNT(DISTINCT eac.id) as agency_coordination_count,
    SUM(CASE WHEN eac.request_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_agencies
FROM events e
LEFT JOIN attendance a ON a.event_id = e.id
LEFT JOIN event_facilitators ef ON ef.event_id = e.id
LEFT JOIN event_audience_segments eas ON eas.event_id = e.id
LEFT JOIN event_partners ep ON ep.event_id = e.id
LEFT JOIN event_agency_coordination eac ON eac.event_id = e.id
GROUP BY e.id;

