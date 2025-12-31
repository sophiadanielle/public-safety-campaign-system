-- Complete Schema Update to Match Specification
-- Public Safety Campaign Management System
-- MySQL 8+ (InnoDB, utf8mb4)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. CAMPAIGNS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to campaigns table (MariaDB compatible)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'category');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN category VARCHAR(100) NULL AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'geographic_scope');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN geographic_scope VARCHAR(255) NULL AFTER category', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'draft_schedule_datetime');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN draft_schedule_datetime DATETIME NULL AFTER end_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'ai_recommended_datetime');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN ai_recommended_datetime DATETIME NULL AFTER draft_schedule_datetime', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'final_schedule_datetime');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN final_schedule_datetime DATETIME NULL AFTER ai_recommended_datetime', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure all planning fields exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'objectives');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN objectives TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'location');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN location VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'assigned_staff');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN assigned_staff JSON NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'barangay_target_zones');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN barangay_target_zones JSON NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'budget');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN budget DECIMAL(12,2) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'staff_count');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN staff_count INT UNSIGNED NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_campaigns' AND COLUMN_NAME = 'materials_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_campaigns` ADD COLUMN materials_json JSON NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Update status enum to include all required statuses
ALTER TABLE `campaign_department_campaigns`
MODIFY COLUMN status ENUM('draft','pending','approved','ongoing','completed','scheduled','active','archived') NOT NULL DEFAULT 'draft';

-- ============================================
-- 2. CONTENTS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to content_items table (MariaDB compatible)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_items' AND COLUMN_NAME = 'hazard_category');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_items` ADD COLUMN hazard_category VARCHAR(100) NULL AFTER content_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_items' AND COLUMN_NAME = 'intended_audience');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_items` ADD COLUMN intended_audience VARCHAR(255) NULL AFTER hazard_category', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_items' AND COLUMN_NAME = 'source');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_items` ADD COLUMN source VARCHAR(255) NULL AFTER intended_audience', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_items' AND COLUMN_NAME = 'approval_status');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_items` ADD COLUMN approval_status ENUM(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\' AFTER source', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_content_items' AND COLUMN_NAME = 'file_path');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_content_items` ADD COLUMN file_path VARCHAR(500) NULL AFTER approval_status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Rename content_items to contents if needed (keeping both for compatibility)
-- ALTER TABLE `campaign_department_content_items` RENAME TO contents;

-- ============================================
-- 3. AUDIENCE_SEGMENTS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to audience_segments table (MariaDB compatible)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'geographic_scope');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN geographic_scope VARCHAR(255) NULL AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'sector_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN sector_type VARCHAR(100) NULL AFTER geographic_scope', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'risk_level');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN risk_level ENUM(\'low\',\'medium\',\'high\') NULL AFTER sector_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_audience_segments' AND COLUMN_NAME = 'segmentation_basis');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN segmentation_basis VARCHAR(255) NULL AFTER risk_level', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================
-- 4. EVENTS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to events table (MariaDB compatible)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'event_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN event_type ENUM(\'seminar\',\'drill\',\'workshop\',\'meeting\',\'other\') NOT NULL DEFAULT \'seminar\' AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'description');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN description TEXT NULL AFTER event_type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'event_date');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN event_date DATE NULL AFTER description', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'event_time');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN event_time TIME NULL AFTER event_date', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'venue');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN venue VARCHAR(255) NULL AFTER location', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'facilitators');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN facilitators JSON NULL AFTER venue', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'attendance_count');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN attendance_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER facilitators', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_events' AND COLUMN_NAME = 'status');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_events` ADD COLUMN status ENUM(\'scheduled\',\'ongoing\',\'completed\',\'cancelled\') NOT NULL DEFAULT \'scheduled\' AFTER attendance_count', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Update existing events to have event_date and event_time from starts_at
UPDATE `campaign_department_events` SET event_date = DATE(starts_at), event_time = TIME(starts_at) WHERE event_date IS NULL;

-- ============================================
-- 5. PARTNERS TABLE ENHANCEMENTS
-- ============================================

-- Add missing fields to partners table (MariaDB compatible)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_partners' AND COLUMN_NAME = 'organization_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_partners` ADD COLUMN organization_type ENUM(\'school\',\'ngo\',\'government\',\'private\',\'other\') NOT NULL DEFAULT \'ngo\' AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_partners' AND COLUMN_NAME = 'contact_details');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_partners` ADD COLUMN contact_details JSON NULL AFTER contact_phone', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_partners' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `campaign_department_partners` ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================
-- 6. FEEDBACK TABLE (if not exists)
-- ============================================

CREATE TABLE IF NOT EXISTS `campaign_department_feedback` (
    feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    rating INT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE,
    INDEX idx_survey_id (survey_id),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ROLES AND PERMISSIONS SEED DATA
-- ============================================

-- Insert roles if they don't exist
INSERT IGNORE INTO `campaign_department_roles` (id, name, description) VALUES
(1, 'Barangay Administrator', 'Full access to all campaign management features'),
(2, 'Barangay Staff', 'Can create and manage campaigns, limited administrative access'),
(3, 'School Partner', 'Can view campaigns and coordinate joint activities'),
(4, 'NGO Partner', 'Can view campaigns and coordinate joint activities');

-- Insert basic permissions
INSERT IGNORE INTO `campaign_department_permissions` (id, name, description) VALUES
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
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 1, id FROM `campaign_department_permissions`;

-- Barangay Staff gets most permissions except user management
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 2, id FROM `campaign_department_permissions` WHERE id != 11;

-- Partners get limited permissions
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 3, id FROM `campaign_department_permissions` WHERE id IN (7, 8, 10);
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 4, id FROM `campaign_department_permissions` WHERE id IN (7, 8, 10);

-- ============================================
-- 8. INDEXES FOR PERFORMANCE
-- ============================================

-- Campaign indexes
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON `campaign_department_campaigns`(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_start_date ON `campaign_department_campaigns`(start_date);
CREATE INDEX IF NOT EXISTS idx_campaigns_category ON `campaign_department_campaigns`(category);
CREATE INDEX IF NOT EXISTS idx_campaigns_owner ON `campaign_department_campaigns`(owner_id);

-- Content indexes
CREATE INDEX IF NOT EXISTS idx_content_approval ON `campaign_department_content_items`(approval_status);
CREATE INDEX IF NOT EXISTS idx_content_hazard ON `campaign_department_content_items`(hazard_category);
CREATE INDEX IF NOT EXISTS idx_content_campaign ON `campaign_department_content_items`(campaign_id);

-- Event indexes
CREATE INDEX IF NOT EXISTS idx_events_date ON `campaign_department_events`(event_date);
CREATE INDEX IF NOT EXISTS idx_events_type ON `campaign_department_events`(event_type);
CREATE INDEX IF NOT EXISTS idx_events_status ON `campaign_department_events`(status);

-- Survey indexes
CREATE INDEX IF NOT EXISTS idx_surveys_campaign ON `campaign_department_surveys`(campaign_id);
CREATE INDEX IF NOT EXISTS idx_feedback_survey ON `campaign_department_feedback`(survey_id);

-- Partner indexes
CREATE INDEX IF NOT EXISTS idx_partners_type ON `campaign_department_partners`(organization_type);

-- ============================================
-- 9. VIEWS FOR REPORTING
-- ============================================

-- Campaign engagement summary view
CREATE OR REPLACE VIEW `campaign_department_campaign_engagement_summary` AS
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
FROM `campaign_department_campaigns` c
LEFT JOIN `campaign_department_campaign_audience` ca ON c.id = ca.campaign_id
LEFT JOIN `campaign_department_events` e ON c.id = e.campaign_id
LEFT JOIN `campaign_department_surveys` s ON c.id = s.campaign_id
LEFT JOIN `campaign_department_survey_responses` sr ON s.id = sr.survey_id
LEFT JOIN `campaign_department_feedback` f ON s.id = f.survey_id
GROUP BY c.id, c.title, c.status;

-- Timing effectiveness comparison view
CREATE OR REPLACE VIEW `campaign_department_timing_effectiveness` AS
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
FROM `campaign_department_campaigns` c
LEFT JOIN `campaign_department_events` e ON c.id = e.campaign_id
LEFT JOIN `campaign_department_surveys` s ON c.id = s.campaign_id
LEFT JOIN `campaign_department_feedback` f ON s.id = f.survey_id
WHERE c.final_schedule_datetime IS NOT NULL
GROUP BY c.id, c.title, c.ai_recommended_datetime, c.final_schedule_datetime;















