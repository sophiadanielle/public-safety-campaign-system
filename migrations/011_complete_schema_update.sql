-- Complete Schema Update to Match Specification
-- Public Safety Campaign Management System
-- MySQL 8+ (InnoDB, utf8mb4)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. CAMPAIGNS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to campaigns table
ALTER TABLE campaigns
ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL AFTER description,
ADD COLUMN IF NOT EXISTS geographic_scope VARCHAR(255) NULL AFTER category,
ADD COLUMN IF NOT EXISTS draft_schedule_datetime DATETIME NULL AFTER end_date,
ADD COLUMN IF NOT EXISTS ai_recommended_datetime DATETIME NULL AFTER draft_schedule_datetime,
ADD COLUMN IF NOT EXISTS final_schedule_datetime DATETIME NULL AFTER ai_recommended_datetime;

-- Ensure all planning fields exist
ALTER TABLE campaigns
ADD COLUMN IF NOT EXISTS objectives TEXT NULL,
ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS assigned_staff JSON NULL,
ADD COLUMN IF NOT EXISTS barangay_target_zones JSON NULL,
ADD COLUMN IF NOT EXISTS budget DECIMAL(12,2) NULL,
ADD COLUMN IF NOT EXISTS staff_count INT UNSIGNED NULL,
ADD COLUMN IF NOT EXISTS materials_json JSON NULL;

-- Update status enum to include all required statuses
ALTER TABLE campaigns
MODIFY COLUMN status ENUM('draft','pending','approved','ongoing','completed','scheduled','active','archived') NOT NULL DEFAULT 'draft';

-- ============================================
-- 2. CONTENTS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to content_items table
ALTER TABLE content_items
ADD COLUMN IF NOT EXISTS hazard_category VARCHAR(100) NULL AFTER content_type,
ADD COLUMN IF NOT EXISTS intended_audience VARCHAR(255) NULL AFTER hazard_category,
ADD COLUMN IF NOT EXISTS source VARCHAR(255) NULL AFTER intended_audience,
ADD COLUMN IF NOT EXISTS approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER source,
ADD COLUMN IF NOT EXISTS file_path VARCHAR(500) NULL AFTER approval_status;

-- Rename content_items to contents if needed (keeping both for compatibility)
-- ALTER TABLE content_items RENAME TO contents;

-- ============================================
-- 3. AUDIENCE_SEGMENTS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to audience_segments table
ALTER TABLE audience_segments
ADD COLUMN IF NOT EXISTS geographic_scope VARCHAR(255) NULL AFTER name,
ADD COLUMN IF NOT EXISTS sector_type VARCHAR(100) NULL AFTER geographic_scope,
ADD COLUMN IF NOT EXISTS risk_level ENUM('low','medium','high') NULL AFTER sector_type,
ADD COLUMN IF NOT EXISTS segmentation_basis VARCHAR(255) NULL AFTER risk_level;

-- ============================================
-- 4. EVENTS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to events table
ALTER TABLE events
ADD COLUMN IF NOT EXISTS event_type ENUM('seminar','drill','workshop','meeting','other') NOT NULL DEFAULT 'seminar' AFTER name,
ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER event_type,
ADD COLUMN IF NOT EXISTS event_date DATE NULL AFTER description,
ADD COLUMN IF NOT EXISTS event_time TIME NULL AFTER event_date,
ADD COLUMN IF NOT EXISTS venue VARCHAR(255) NULL AFTER location,
ADD COLUMN IF NOT EXISTS facilitators JSON NULL AFTER venue,
ADD COLUMN IF NOT EXISTS attendance_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER facilitators,
ADD COLUMN IF NOT EXISTS status ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled' AFTER attendance_count;

-- Update existing events to have event_date and event_time from starts_at
UPDATE events SET event_date = DATE(starts_at), event_time = TIME(starts_at) WHERE event_date IS NULL;

-- ============================================
-- 5. PARTNERS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to partners table
ALTER TABLE partners
ADD COLUMN IF NOT EXISTS organization_type ENUM('school','ngo','government','private','other') NOT NULL DEFAULT 'ngo' AFTER name,
ADD COLUMN IF NOT EXISTS contact_details JSON NULL AFTER contact_phone,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- ============================================
-- 6. FEEDBACK TABLE (if not exists)
-- ============================================

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    rating INT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_survey FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    INDEX idx_survey_id (survey_id),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ROLES AND PERMISSIONS SEED DATA
-- ============================================

-- Insert roles if they don't exist
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'Barangay Administrator', 'Full access to all campaign management features'),
(2, 'Barangay Staff', 'Can create and manage campaigns, limited administrative access'),
(3, 'School Partner', 'Can view campaigns and coordinate joint activities'),
(4, 'NGO Partner', 'Can view campaigns and coordinate joint activities');

-- Insert basic permissions
INSERT IGNORE INTO permissions (id, name, description) VALUES
(1, 'campaigns.create', 'Create new campaigns'),
(2, 'campaigns.update', 'Update existing campaigns'),
(3, 'campaigns.delete', 'Delete campaigns'),
(4, 'campaigns.approve', 'Approve campaigns'),
(5, 'content.manage', 'Manage content repository'),
(6, 'content.approve', 'Approve content'),
(7, 'events.manage', 'Manage events and seminars'),
(8, 'surveys.manage', 'Create and manage surveys'),
(9, 'partners.manage', 'Manage partner organizations'),
(10, 'reports.view', 'View reports and analytics'),
(11, 'users.manage', 'Manage users (admin only)');

-- Assign permissions to roles
-- Barangay Administrator gets all permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Barangay Staff gets most permissions except user management
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE id != 11;

-- Partners get limited permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE id IN (7, 8, 10);
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE id IN (7, 8, 10);

-- ============================================
-- 8. INDEXES FOR PERFORMANCE
-- ============================================

-- Campaign indexes
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON campaigns(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_start_date ON campaigns(start_date);
CREATE INDEX IF NOT EXISTS idx_campaigns_category ON campaigns(category);
CREATE INDEX IF NOT EXISTS idx_campaigns_owner ON campaigns(owner_id);

-- Content indexes
CREATE INDEX IF NOT EXISTS idx_content_approval ON content_items(approval_status);
CREATE INDEX IF NOT EXISTS idx_content_hazard ON content_items(hazard_category);
CREATE INDEX IF NOT EXISTS idx_content_campaign ON content_items(campaign_id);

-- Event indexes
CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date);
CREATE INDEX IF NOT EXISTS idx_events_type ON events(event_type);
CREATE INDEX IF NOT EXISTS idx_events_status ON events(status);

-- Survey indexes
CREATE INDEX IF NOT EXISTS idx_surveys_campaign ON surveys(campaign_id);
CREATE INDEX IF NOT EXISTS idx_feedback_survey ON feedback(survey_id);

-- Partner indexes
CREATE INDEX IF NOT EXISTS idx_partners_type ON partners(organization_type);

-- ============================================
-- 9. VIEWS FOR REPORTING
-- ============================================

-- Campaign engagement summary view
CREATE OR REPLACE VIEW campaign_engagement_summary AS
SELECT 
    c.id AS campaign_id,
    c.title,
    c.status,
    COUNT(DISTINCT ca.segment_id) AS segment_count,
    COUNT(DISTINCT e.id) AS event_count,
    COALESCE(SUM(e.attendance_count), 0) AS total_attendance,
    COUNT(DISTINCT s.id) AS survey_count,
    COUNT(DISTINCT sr.id) AS response_count,
    AVG(f.rating) AS avg_rating
FROM campaigns c
LEFT JOIN campaign_audience ca ON c.id = ca.campaign_id
LEFT JOIN events e ON c.id = e.campaign_id
LEFT JOIN surveys s ON c.id = s.campaign_id
LEFT JOIN survey_responses sr ON s.id = sr.survey_id
LEFT JOIN feedback f ON s.id = f.survey_id
GROUP BY c.id, c.title, c.status;

-- Timing effectiveness comparison view
CREATE OR REPLACE VIEW timing_effectiveness AS
SELECT 
    c.id AS campaign_id,
    c.title,
    c.ai_recommended_datetime,
    c.final_schedule_datetime,
    CASE 
        WHEN c.final_schedule_datetime = c.ai_recommended_datetime THEN 'AI Used'
        ELSE 'Manual Override'
    END AS scheduling_method,
    COALESCE(SUM(e.attendance_count), 0) AS attendance,
    AVG(f.rating) AS avg_rating
FROM campaigns c
LEFT JOIN events e ON c.id = e.campaign_id
LEFT JOIN surveys s ON c.id = s.campaign_id
LEFT JOIN feedback f ON s.id = f.survey_id
WHERE c.final_schedule_datetime IS NOT NULL
GROUP BY c.id, c.title, c.ai_recommended_datetime, c.final_schedule_datetime;















