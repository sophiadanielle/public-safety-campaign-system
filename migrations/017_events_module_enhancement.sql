-- Event & Seminar Management Module Enhancement
-- Migration 017: Complete events module schema update
-- System: Public Safety Campaign Management Scheduler for Barangays in Quezon City
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. UPDATE EVENTS TABLE SCHEMA
-- ============================================

-- Rename 'name' to 'event_name' (if column exists)
ALTER TABLE events CHANGE COLUMN name event_name VARCHAR(200) NOT NULL;

-- Rename 'campaign_id' to 'linked_campaign_id' (if column exists)
ALTER TABLE events CHANGE COLUMN campaign_id linked_campaign_id INT UNSIGNED NULL;

-- Modify event_type enum
ALTER TABLE events MODIFY COLUMN event_type ENUM('seminar', 'drill', 'orientation', 'clean-up', 'simulation') NOT NULL DEFAULT 'seminar';

-- Add new columns (will fail gracefully if they exist)
ALTER TABLE events ADD COLUMN description TEXT NULL AFTER event_type;
ALTER TABLE events ADD COLUMN date DATE NULL AFTER description;
ALTER TABLE events ADD COLUMN start_time TIME NULL AFTER date;
ALTER TABLE events ADD COLUMN end_time TIME NULL AFTER start_time;
ALTER TABLE events ADD COLUMN venue VARCHAR(255) NULL AFTER location;
ALTER TABLE events ADD COLUMN venue_map_coordinates VARCHAR(255) NULL AFTER venue;
ALTER TABLE events ADD COLUMN capacity INT UNSIGNED NULL AFTER venue_map_coordinates;

-- Drop old status column if exists, then add event_status
ALTER TABLE events DROP COLUMN status;
ALTER TABLE events ADD COLUMN event_status ENUM('planned', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'planned';

-- Add created_by column
ALTER TABLE events ADD COLUMN created_by INT UNSIGNED NULL AFTER event_status;

-- Update existing data: derive date and times from starts_at/ends_at
UPDATE events 
SET date = DATE(starts_at), 
    start_time = TIME(starts_at),
    end_time = TIME(ends_at)
WHERE date IS NULL AND starts_at IS NOT NULL;

-- Set default event_status to 'planned' if NULL
UPDATE events SET event_status = 'planned' WHERE event_status IS NULL;

-- ============================================
-- 2. CREATE JUNCTION TABLES
-- ============================================

-- Event Facilitators (many-to-many: events <-> users)
CREATE TABLE IF NOT EXISTS event_facilitators (
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (event_id, user_id),
    CONSTRAINT fk_event_facilitators_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_facilitators_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_facilitators_event (event_id),
    INDEX idx_event_facilitators_user (user_id)
) ENGINE=InnoDB;

-- Event Audience Segments (many-to-many: events <-> audience_segments)
CREATE TABLE IF NOT EXISTS event_audience_segments (
    event_id INT UNSIGNED NOT NULL,
    segment_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (event_id, segment_id),
    CONSTRAINT fk_event_audience_segments_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_audience_segments_segment FOREIGN KEY (segment_id) REFERENCES audience_segments(id) ON DELETE CASCADE,
    INDEX idx_event_audience_segments_event (event_id),
    INDEX idx_event_audience_segments_segment (segment_id)
) ENGINE=InnoDB;

-- Ensure partners table exists (create if not exists)
CREATE TABLE IF NOT EXISTS partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    organization_type ENUM('school', 'ngo', 'agency', 'other') NOT NULL DEFAULT 'other',
    contact_name VARCHAR(150) NULL,
    contact_email VARCHAR(150) NULL,
    contact_phone VARCHAR(50) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_partners_type (organization_type)
) ENGINE=InnoDB;

-- Event Partners (many-to-many: events <-> partners)
CREATE TABLE IF NOT EXISTS event_partners (
    event_id INT UNSIGNED NOT NULL,
    partner_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (event_id, partner_id),
    CONSTRAINT fk_event_partners_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_partners_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    INDEX idx_event_partners_event (event_id),
    INDEX idx_event_partners_partner (partner_id)
) ENGINE=InnoDB;

-- ============================================
-- 3. UPDATE ATTENDANCE TABLE
-- ============================================

-- Update attendance table to match new requirements
ALTER TABLE attendance CHANGE COLUMN id attendance_id INT UNSIGNED AUTO_INCREMENT;
ALTER TABLE attendance ADD COLUMN participant_identifier VARCHAR(255) NULL AFTER event_id;
ALTER TABLE attendance ADD COLUMN checkin_method ENUM('QR', 'manual') NOT NULL DEFAULT 'manual' AFTER participant_identifier;
ALTER TABLE attendance CHANGE COLUMN check_in checkin_timestamp DATETIME NOT NULL;
ALTER TABLE attendance DROP COLUMN check_out;
ALTER TABLE attendance MODIFY COLUMN audience_member_id INT UNSIGNED NULL;

-- ============================================
-- 4. CREATE EVENT LOGISTICS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS event_logistics (
    event_id INT UNSIGNED NOT NULL PRIMARY KEY,
    resources_list JSON NULL,
    volunteer_roster JSON NULL,
    partner_coordination_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_logistics_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 5. ADD NEW PERMISSIONS
-- ============================================

-- Insert new event-related permissions
INSERT IGNORE INTO permissions (name, description) VALUES
    ('EVENT_CREATE', 'Create events and seminars'),
    ('EVENT_EDIT', 'Edit events and seminars'),
    ('EVENT_CHECKIN', 'Check in attendees to events'),
    ('CAMPAIGN_VIEW', 'View campaigns'),
    ('SEGMENT_VIEW', 'View audience segments'),
    ('PARTNER_MANAGE', 'Manage partner organizations'),
    ('IMPACT_VIEW', 'View impact monitoring reports'),
    ('ADMIN_USER_MANAGE', 'Manage users (admin only)');

-- ============================================
-- 6. ASSIGN PERMISSIONS TO ROLES
-- ============================================

-- Create roles if they don't exist
INSERT IGNORE INTO roles (name, description) VALUES
    ('system_admin', 'System Administrator - Full access'),
    ('barangay_admin', 'Barangay Administrator - Create/edit events, assign partners'),
    ('campaign_creator', 'Campaign Creator - Create campaigns and events'),
    ('content_manager', 'Content Manager - Manage content repository'),
    ('volunteer_coordinator', 'Volunteer Coordinator - Attendance & check-ins'),
    ('partner_contact', 'Partner Contact - View assigned events, upload materials'),
    ('analyst', 'Analyst - View reports and exports'),
    ('resident', 'Resident - View public events only');

-- Get role IDs and assign permissions
-- system_admin → full access (all permissions)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'system_admin';

-- barangay_admin → EVENT_CREATE, EVENT_EDIT, PARTNER_MANAGE, CAMPAIGN_VIEW, SEGMENT_VIEW, IMPACT_VIEW
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'barangay_admin' 
AND p.name IN ('EVENT_CREATE', 'EVENT_EDIT', 'PARTNER_MANAGE', 'CAMPAIGN_VIEW', 'SEGMENT_VIEW', 'IMPACT_VIEW');

-- campaign_creator → EVENT_CREATE, EVENT_EDIT, CAMPAIGN_VIEW, SEGMENT_VIEW
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'campaign_creator' 
AND p.name IN ('EVENT_CREATE', 'EVENT_EDIT', 'CAMPAIGN_VIEW', 'SEGMENT_VIEW');

-- volunteer_coordinator → EVENT_CHECKIN, EVENT_EDIT, CAMPAIGN_VIEW
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'volunteer_coordinator' 
AND p.name IN ('EVENT_CHECKIN', 'EVENT_EDIT', 'CAMPAIGN_VIEW');

-- partner_contact → CAMPAIGN_VIEW, SEGMENT_VIEW
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'partner_contact' 
AND p.name IN ('CAMPAIGN_VIEW', 'SEGMENT_VIEW');

-- analyst → IMPACT_VIEW, CAMPAIGN_VIEW, SEGMENT_VIEW
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'analyst' 
AND p.name IN ('IMPACT_VIEW', 'CAMPAIGN_VIEW', 'SEGMENT_VIEW');

-- ============================================
-- 7. ADD INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX IF NOT EXISTS idx_events_date ON events(date);
CREATE INDEX IF NOT EXISTS idx_events_event_status ON events(event_status);
CREATE INDEX IF NOT EXISTS idx_events_event_type ON events(event_type);
CREATE INDEX IF NOT EXISTS idx_events_created_by ON events(created_by);
CREATE INDEX IF NOT EXISTS idx_events_linked_campaign_id ON events(linked_campaign_id);
CREATE INDEX IF NOT EXISTS idx_attendance_event_id ON attendance(event_id);
CREATE INDEX IF NOT EXISTS idx_attendance_checkin_timestamp ON attendance(checkin_timestamp);
CREATE INDEX IF NOT EXISTS idx_attendance_checkin_method ON attendance(checkin_method);

-- ============================================
-- 8. ADD FOREIGN KEY FOR CREATED_BY
-- ============================================

-- Add foreign key constraint for created_by (if it doesn't exist)
ALTER TABLE events ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================
-- 9. UPDATE EXISTING DATA (if needed)
-- ============================================

-- Set created_by for existing events (if not set)
UPDATE events e
SET e.created_by = (
    SELECT c.owner_id 
    FROM campaigns c 
    WHERE c.id = e.linked_campaign_id 
    LIMIT 1
)
WHERE e.created_by IS NULL AND e.linked_campaign_id IS NOT NULL;
