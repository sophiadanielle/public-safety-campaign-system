-- ============================================
-- Public Safety Campaign Management System
-- Complete Database Schema and Seed Data
-- MySQL 8+ Compatible
-- ============================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- ============================================
-- BASE TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS `campaign_department_roles` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_permissions` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_role_permissions` (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_barangays` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    city VARCHAR(150) NULL,
    province VARCHAR(150) NULL,
    region VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_users` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    barangay_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_campaigns` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    geographic_scope VARCHAR(255) NULL,
    status ENUM('draft','pending','approved','ongoing','completed','scheduled','active','archived') NOT NULL DEFAULT 'draft',
    start_date DATE NULL,
    end_date DATE NULL,
    draft_schedule_datetime DATETIME NULL,
    ai_recommended_datetime DATETIME NULL,
    final_schedule_datetime DATETIME NULL,
    owner_id INT UNSIGNED NOT NULL,
    objectives TEXT NULL,
    location VARCHAR(255) NULL,
    assigned_staff JSON NULL,
    barangay_target_zones JSON NULL,
    budget DECIMAL(12,2) NULL,
    staff_count INT UNSIGNED NULL,
    materials_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_campaign_schedules` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    scheduled_at DATETIME NOT NULL,
    channel VARCHAR(100) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_content_items` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NULL,
    content_type ENUM('text','image','video','link','file','poster','guideline','infographic') NOT NULL DEFAULT 'text',
    hazard_category VARCHAR(100) NULL,
    intended_audience_segment VARCHAR(255) NULL,
    source VARCHAR(255) NULL,
    approval_status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    approved_by INT UNSIGNED NULL,
    approval_notes TEXT NULL,
    file_path VARCHAR(500) NULL,
    file_reference VARCHAR(500) NULL,
    date_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visibility ENUM('public','internal','restricted') NOT NULL DEFAULT 'public',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_attachments` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_audience_segments` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    segment_name VARCHAR(150) NOT NULL UNIQUE,
    geographic_scope ENUM('Barangay', 'Zone', 'Purok') NULL,
    location_reference VARCHAR(255) NULL,
    sector_type ENUM('Households', 'Youth', 'Senior Citizens', 'Schools', 'NGOs') NULL,
    risk_level ENUM('Low', 'Medium', 'High') NULL,
    basis_of_segmentation ENUM('Historical trend', 'Inspection results', 'Attendance records', 'Incident pattern reference') NULL,
    criteria JSON NULL,
    demographics_json JSON NULL,
    geographies_json JSON NULL,
    preferences_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_audience_members` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    segment_id INT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    sector VARCHAR(100) NULL,
    barangay VARCHAR(255) NULL,
    zone VARCHAR(255) NULL,
    purok VARCHAR(255) NULL,
    contact VARCHAR(150) NULL,
    channel ENUM('sms','email','push','social','other') DEFAULT 'other',
    risk_level VARCHAR(50) NULL,
    geo VARCHAR(150) NULL,
    preferences_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_campaign_audience` (
    campaign_id INT UNSIGNED NOT NULL,
    segment_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (campaign_id, segment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_events` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(200) NULL,
    event_name VARCHAR(200) NOT NULL,
    linked_campaign_id INT UNSIGNED NULL,
    event_type ENUM('seminar','drill','workshop','orientation','meeting','other') NOT NULL DEFAULT 'seminar',
    event_description TEXT NULL,
    description TEXT NULL,
    hazard_focus VARCHAR(255) NULL,
    target_audience_profile_id INT UNSIGNED NULL,
    date DATE NULL,
    event_date DATE NULL,
    start_time TIME NULL,
    event_time TIME NULL,
    end_time TIME NULL,
    location VARCHAR(255) NULL,
    venue VARCHAR(255) NULL,
    venue_map_coordinates VARCHAR(255) NULL,
    capacity INT UNSIGNED NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    logistics_json JSON NULL,
    materials_json JSON NULL,
    facilitators JSON NULL,
    event_status ENUM('draft','scheduled','confirmed','ongoing','completed','cancelled','planned') NOT NULL DEFAULT 'draft',
    status ENUM('scheduled','ongoing','completed','cancelled') NULL,
    transport_requirements TEXT NULL,
    trainer_requirements TEXT NULL,
    equipment_requirements TEXT NULL,
    volunteer_requirements TEXT NULL,
    created_by INT UNSIGNED NULL,
    last_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    attendance_count INT UNSIGNED NOT NULL DEFAULT 0,
    post_event_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_attendance` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    audience_member_id INT UNSIGNED NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_surveys` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_survey_questions` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('text','single_choice','multiple_choice','rating') NOT NULL DEFAULT 'text',
    options_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_survey_responses` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    audience_member_id INT UNSIGNED NULL,
    responses_json JSON NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_impact_metrics` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    metric_name VARCHAR(150) NOT NULL,
    metric_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_partners` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    organization_type ENUM('school','ngo','government','private','other') NOT NULL DEFAULT 'ngo',
    contact_person VARCHAR(150) NULL,
    contact_email VARCHAR(150) NULL,
    contact_phone VARCHAR(50) NULL,
    contact_details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_partner_engagements` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id INT UNSIGNED NOT NULL,
    campaign_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    engagement_type VARCHAR(100) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_automl_predictions` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    prediction JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_integration_logs` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(100) NOT NULL,
    payload JSON NULL,
    status ENUM('success','failed') NOT NULL DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_notification_logs` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NULL,
    audience_member_id INT UNSIGNED NULL,
    channel VARCHAR(50) NOT NULL,
    status ENUM('sent','failed','queued') NOT NULL DEFAULT 'queued',
    response_message VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_audit_logs` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_tags` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_content_usage` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NULL,
    event_id INT UNSIGNED NULL,
    survey_id INT UNSIGNED NULL,
    usage_context VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_feedback` (
    feedback_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    rating INT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_content_item_versions` (
    version_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_id INT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    file_reference VARCHAR(500) NULL,
    file_path VARCHAR(500) NULL,
    changed_by INT UNSIGNED NULL,
    change_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_content_item_version (content_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_campaign_content_items` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    attached_by INT UNSIGNED NULL,
    attached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_campaign_content_item (campaign_id, content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_reference_locations` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    barangay_name VARCHAR(150) NULL,
    city VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_reference_staff` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    role VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_department_content_tags` (
    content_item_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (content_item_id, tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX IF NOT EXISTS idx_campaigns_status ON `campaign_department_campaigns`(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_start_date ON `campaign_department_campaigns`(start_date);
CREATE INDEX IF NOT EXISTS idx_campaigns_category ON `campaign_department_campaigns`(category);
CREATE INDEX IF NOT EXISTS idx_campaigns_owner ON `campaign_department_campaigns`(owner_id);
CREATE INDEX IF NOT EXISTS idx_content_items_type ON `campaign_department_content_items`(content_type);
CREATE INDEX IF NOT EXISTS idx_content_items_hazard ON `campaign_department_content_items`(hazard_category);
CREATE INDEX IF NOT EXISTS idx_content_items_status ON `campaign_department_content_items`(approval_status);
CREATE INDEX IF NOT EXISTS idx_content_items_campaign ON `campaign_department_content_items`(campaign_id);
CREATE INDEX IF NOT EXISTS idx_content_items_audience ON `campaign_department_content_items`(intended_audience_segment(100));
CREATE INDEX IF NOT EXISTS idx_events_date ON `campaign_department_events`(date);
CREATE INDEX IF NOT EXISTS idx_events_event_date ON `campaign_department_events`(event_date);
CREATE INDEX IF NOT EXISTS idx_events_type ON `campaign_department_events`(event_type);
CREATE INDEX IF NOT EXISTS idx_events_event_status ON `campaign_department_events`(event_status);
CREATE INDEX IF NOT EXISTS idx_events_linked_campaign ON `campaign_department_events`(linked_campaign_id);
CREATE INDEX IF NOT EXISTS idx_events_target_audience ON `campaign_department_events`(target_audience_profile_id);
CREATE INDEX IF NOT EXISTS idx_events_created_by ON `campaign_department_events`(created_by);
CREATE INDEX IF NOT EXISTS idx_events_venue ON `campaign_department_events`(venue);
CREATE INDEX IF NOT EXISTS idx_surveys_campaign ON `campaign_department_surveys`(campaign_id);
CREATE INDEX IF NOT EXISTS idx_feedback_survey ON `campaign_department_feedback`(survey_id);
CREATE INDEX IF NOT EXISTS idx_partners_type ON `campaign_department_partners`(organization_type);
CREATE INDEX IF NOT EXISTS idx_content_item_versions_content ON `campaign_department_content_item_versions`(content_id);
CREATE INDEX IF NOT EXISTS idx_campaign_content_items_campaign ON `campaign_department_campaign_content_items`(campaign_id);
CREATE INDEX IF NOT EXISTS idx_campaign_content_items_content ON `campaign_department_campaign_content_items`(content_id);

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

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
LEFT JOIN `campaign_department_events` e ON c.id = e.linked_campaign_id
LEFT JOIN `campaign_department_surveys` s ON c.id = s.campaign_id
LEFT JOIN `campaign_department_survey_responses` sr ON s.id = sr.survey_id
LEFT JOIN `campaign_department_feedback` f ON s.id = f.survey_id
GROUP BY c.id, c.title, c.status;

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
LEFT JOIN `campaign_department_events` e ON c.id = e.linked_campaign_id
LEFT JOIN `campaign_department_surveys` s ON c.id = s.campaign_id
LEFT JOIN `campaign_department_feedback` f ON s.id = f.survey_id
WHERE c.final_schedule_datetime IS NOT NULL
GROUP BY c.id, c.title, c.ai_recommended_datetime, c.final_schedule_datetime;

CREATE OR REPLACE VIEW `participation_history` AS
SELECT 
    c.id AS campaign_id,
    c.title AS campaign_name,
    e.id AS event_id,
    e.event_name,
    e.event_type,
    e.date AS event_date,
    e.event_date,
    e.attendance_count,
    a.check_in,
    a.check_out,
    am.id AS member_id,
    am.full_name AS member_name,
    s.id AS segment_id,
    s.segment_name
FROM `campaign_department_campaigns` c
LEFT JOIN `campaign_department_events` e ON e.linked_campaign_id = c.id
LEFT JOIN `campaign_department_attendance` a ON a.event_id = e.id
LEFT JOIN `campaign_department_audience_members` am ON am.id = a.audience_member_id
LEFT JOIN `campaign_department_audience_segments` s ON s.id = am.segment_id
ORDER BY e.date DESC, a.check_in DESC;

-- ============================================
-- FOREIGN KEY CONSTRAINTS
-- ============================================

ALTER TABLE `campaign_department_role_permissions`
    ADD CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES `campaign_department_roles`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES `campaign_department_permissions`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_users`
    ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES `campaign_department_roles`(id),
    ADD CONSTRAINT fk_users_barangay FOREIGN KEY (barangay_id) REFERENCES `campaign_department_barangays`(id);

ALTER TABLE `campaign_department_campaigns`
    ADD CONSTRAINT fk_campaigns_owner FOREIGN KEY (owner_id) REFERENCES `campaign_department_users`(id);

ALTER TABLE `campaign_department_campaign_schedules`
    ADD CONSTRAINT fk_campaign_schedules_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_content_items`
    ADD CONSTRAINT fk_content_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_content_creator FOREIGN KEY (created_by) REFERENCES `campaign_department_users`(id),
    ADD CONSTRAINT fk_content_approver FOREIGN KEY (approved_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_attachments`
    ADD CONSTRAINT fk_attachments_content FOREIGN KEY (content_item_id) REFERENCES `campaign_department_content_items`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_audience_members`
    ADD CONSTRAINT fk_audience_members_segment FOREIGN KEY (segment_id) REFERENCES `campaign_department_audience_segments`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_campaign_audience`
    ADD CONSTRAINT fk_campaign_audience_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_campaign_audience_segment FOREIGN KEY (segment_id) REFERENCES `campaign_department_audience_segments`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_events`
    ADD CONSTRAINT fk_events_campaign FOREIGN KEY (linked_campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_events_target_audience FOREIGN KEY (target_audience_profile_id) REFERENCES `campaign_department_audience_segments`(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_events_created_by FOREIGN KEY (created_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_attendance`
    ADD CONSTRAINT fk_attendance_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_attendance_member FOREIGN KEY (audience_member_id) REFERENCES `campaign_department_audience_members`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_surveys`
    ADD CONSTRAINT fk_surveys_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_surveys_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_survey_questions`
    ADD CONSTRAINT fk_questions_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_survey_responses`
    ADD CONSTRAINT fk_responses_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_responses_member FOREIGN KEY (audience_member_id) REFERENCES `campaign_department_audience_members`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_impact_metrics`
    ADD CONSTRAINT fk_metrics_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_partner_engagements`
    ADD CONSTRAINT fk_partner_engagements_partner FOREIGN KEY (partner_id) REFERENCES `campaign_department_partners`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_partner_engagements_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_partner_engagements_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_automl_predictions`
    ADD CONSTRAINT fk_predictions_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_notification_logs`
    ADD CONSTRAINT fk_notifications_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_notifications_member FOREIGN KEY (audience_member_id) REFERENCES `campaign_department_audience_members`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_audit_logs`
    ADD CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_content_usage`
    ADD CONSTRAINT fk_content_usage_content FOREIGN KEY (content_item_id) REFERENCES `campaign_department_content_items`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_content_usage_tag FOREIGN KEY (tag_id) REFERENCES `campaign_department_tags`(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_content_usage_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_content_usage_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_feedback`
    ADD CONSTRAINT fk_feedback_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE;

ALTER TABLE `campaign_department_content_item_versions`
    ADD CONSTRAINT fk_content_item_versions_content FOREIGN KEY (content_id) REFERENCES `campaign_department_content_items`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_content_item_versions_user FOREIGN KEY (changed_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_campaign_content_items`
    ADD CONSTRAINT fk_campaign_content_items_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_campaign_content_items_content FOREIGN KEY (content_id) REFERENCES `campaign_department_content_items`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_campaign_content_items_user FOREIGN KEY (attached_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL;

ALTER TABLE `campaign_department_content_tags`
    ADD CONSTRAINT fk_content_tags_content FOREIGN KEY (content_item_id) REFERENCES `campaign_department_content_items`(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_content_tags_tag FOREIGN KEY (tag_id) REFERENCES `campaign_department_tags`(id) ON DELETE CASCADE;

-- ============================================
-- SEED DATA
-- ============================================

-- BARANGAYS
INSERT IGNORE INTO `campaign_department_barangays` (id, name, city, province, region) VALUES
(1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR'),
(2, 'Barangay 2', 'Quezon City', 'Metro Manila', 'NCR'),
(3, 'Barangay 3', 'Quezon City', 'Metro Manila', 'NCR'),
(4, 'Commonwealth', 'Quezon City', 'Metro Manila', 'NCR'),
(5, 'Batasan Hills', 'Quezon City', 'Metro Manila', 'NCR'),
(6, 'Payatas', 'Quezon City', 'Metro Manila', 'NCR'),
(7, 'Holy Spirit', 'Quezon City', 'Metro Manila', 'NCR'),
(8, 'Bagong Silangan', 'Quezon City', 'Metro Manila', 'NCR'),
(9, 'Tandang Sora', 'Quezon City', 'Metro Manila', 'NCR'),
(10, 'Matandang Balara', 'Quezon City', 'Metro Manila', 'NCR'),
(11, 'Culiat', 'Quezon City', 'Metro Manila', 'NCR'),
(12, 'Nagkaisang Nayon', 'Quezon City', 'Metro Manila', 'NCR'),
(13, 'Krus na Ligas', 'Quezon City', 'Metro Manila', 'NCR'),
(14, 'Pansol', 'Quezon City', 'Metro Manila', 'NCR'),
(15, 'Teachers Village East', 'Quezon City', 'Metro Manila', 'NCR'),
(16, 'Teachers Village West', 'Quezon City', 'Metro Manila', 'NCR'),
(17, 'UP Campus', 'Quezon City', 'Metro Manila', 'NCR'),
(18, 'Loyola Heights', 'Quezon City', 'Metro Manila', 'NCR');

-- ROLES
INSERT IGNORE INTO `campaign_department_roles` (id, name, description) VALUES
(1, 'Barangay Administrator', 'Full access to all campaign management features'),
(2, 'Barangay Staff', 'Can create and manage campaigns, limited administrative access'),
(3, 'School Partner', 'Can view campaigns and coordinate joint activities'),
(4, 'NGO Partner', 'Can view campaigns and coordinate joint activities');

-- PERMISSIONS
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

-- ROLE PERMISSIONS
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 1, id FROM `campaign_department_permissions`;

INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 2, id FROM `campaign_department_permissions` WHERE id != 11;

INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 3, id FROM `campaign_department_permissions` WHERE id IN (7, 8, 10);

INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT 4, id FROM `campaign_department_permissions` WHERE id IN (7, 8, 10);

-- USERS
INSERT IGNORE INTO `campaign_department_users` (id, role_id, barangay_id, name, email, password_hash, is_active) VALUES
(1, 1, 1, 'Admin User', 'admin@barangay1.qc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 2, 1, 'Staff Member', 'staff@barangay1.qc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(3, 3, NULL, 'School Partner', 'school@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(4, 4, NULL, 'NGO Partner', 'ngo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- AUDIENCE SEGMENTS
INSERT IGNORE INTO `campaign_department_audience_segments` (id, segment_name, geographic_scope, location_reference, sector_type, risk_level, basis_of_segmentation, criteria) VALUES
(1, 'Residential Areas - High Risk', 'Barangay', 'Barangay 1-5', 'Households', 'High', 'Historical trend', '{"location": "Barangay 1-5", "risk_factors": ["dense_population", "old_buildings"]}'),
(2, 'School Communities', 'Barangay', 'All Schools', 'Schools', 'Medium', 'Attendance records', '{"type": "school", "age_group": "all"}'),
(3, 'Commercial Districts', 'Zone', 'Business Areas', 'Households', 'Medium', 'Inspection results', '{"type": "commercial", "business_size": "all"}'),
(4, 'Senior Citizens', 'Barangay', 'All Areas', 'Senior Citizens', 'High', 'Historical trend', '{"age_min": 60, "special_needs": true}'),
(5, 'Sample Residents', 'Barangay', NULL, 'Households', NULL, NULL, JSON_OBJECT('channel', JSON_ARRAY('email','sms')));

-- AUDIENCE MEMBERS
INSERT IGNORE INTO `campaign_department_audience_members` (segment_id, full_name, contact, channel) VALUES
(5, 'Ana Santos', 'ana@example.com', 'email'),
(5, 'Ben Cruz', '09171234567', 'sms'),
(5, 'Carla Reyes', 'carla@example.com', 'email'),
(5, 'David Lee', NULL, 'other');

-- PARTNERS
INSERT IGNORE INTO `campaign_department_partners` (id, name, organization_type, contact_person, contact_email, contact_phone, contact_details) VALUES
(1, 'Quezon City Elementary School', 'school', 'Principal Juan Dela Cruz', 'principal@qces.edu.ph', '+63-2-1234-5678', '{"address": "123 Main St, Quezon City", "website": "www.qces.edu.ph"}'),
(2, 'Red Cross Quezon City Chapter', 'ngo', 'Maria Santos', 'maria@redcross.qc.ph', '+63-2-2345-6789', '{"address": "456 Relief Ave, Quezon City", "services": ["disaster_relief", "first_aid_training"]}'),
(3, 'Quezon City High School', 'school', 'Principal Pedro Reyes', 'pedro@qchs.edu.ph', '+63-2-3456-7890', '{"address": "789 Education Blvd, Quezon City", "student_count": 1500}'),
(4, 'Save the Children Philippines', 'ngo', 'Ana Garcia', 'ana@savethechildren.ph', '+63-2-4567-8901', '{"address": "321 Hope St, Quezon City", "focus_areas": ["child_safety", "education"]}');

-- CAMPAIGNS
INSERT IGNORE INTO `campaign_department_campaigns` (id, title, description, category, geographic_scope, status, start_date, end_date, owner_id, objectives, location, assigned_staff, barangay_target_zones, budget, staff_count, materials_json) VALUES
(1, 'Fire Safety Awareness Week 2025', 'Annual fire safety awareness campaign for residential areas', 'fire_safety', 'Quezon City - Barangay 1-5', 'approved', '2025-03-01', '2025-03-07', 1, 'Increase fire safety awareness, distribute fire safety materials, conduct fire drills', 'Barangay Hall - Barangay 1', '["John Doe", "Jane Smith", "Bob Johnson"]', '["Barangay 1", "Barangay 2", "Barangay 3"]', 50000.00, 5, '{"posters": 100, "flyers": 500, "banners": 5}'),
(2, 'Earthquake Preparedness Seminar', 'Educational seminar on earthquake preparedness and response', 'earthquake', 'Quezon City - All Areas', 'draft', '2025-04-15', '2025-04-15', 1, 'Educate residents on earthquake safety, demonstrate proper response procedures', 'Quezon City Convention Center', '["Maria Santos", "Pedro Reyes"]', '["All Barangays"]', 30000.00, 3, '{"brochures": 300, "videos": 2}'),
(3, 'Flood Preparedness Campaign', 'Campaign to prepare communities for flood season', 'flood', 'Quezon City - Low-lying Areas', 'pending', '2025-05-01', '2025-05-31', 2, 'Distribute flood safety information, identify evacuation routes, coordinate with partners', 'Multiple Locations', '["Ana Garcia", "Carlos Mendoza"]', '["Barangay 1", "Barangay 2"]', 75000.00, 8, '{"sandbags": 200, "information_kits": 1000}'),
(4, 'Fire Safety Awareness Drive', 'Barangay-level fire safety awareness drive in Quezon City.', 'fire_safety', 'Quezon City', 'draft', '2025-01-10', '2025-01-31', 1, NULL, 'Commonwealth Covered Court', NULL, NULL, 40000.00, 5, NULL),
(5, 'Flood Preparedness Campaign', 'Preparedness campaign for flood-prone barangays.', 'flood', 'Quezon City', 'draft', '2025-02-01', '2025-02-28', 1, NULL, 'Evacuation Center', NULL, NULL, 60000.00, 6, NULL),
(6, 'Earthquake Readiness Orientation', 'Orientation sessions on earthquake readiness.', 'earthquake', 'Quezon City', 'draft', '2025-03-05', '2025-03-20', 1, NULL, 'Payatas Elementary School', NULL, NULL, 35000.00, 4, NULL),
(7, 'Road Safety Information Drive', 'Information drive on pedestrian and road safety.', 'road_safety', 'Quezon City', 'draft', '2025-04-01', '2025-04-30', 1, NULL, 'Litex Area', NULL, NULL, 30000.00, 3, NULL),
(8, 'Dengue Prevention Campaign', 'Campaign on dengue prevention and clean-up activities.', 'health', 'Quezon City', 'draft', '2025-05-01', '2025-05-31', 1, NULL, 'Bagong Silangan Multi-Purpose Hall', NULL, NULL, 45000.00, 5, NULL),
(9, 'Fire Drill Awareness Program', 'Program to promote regular community fire drills.', 'fire_safety', 'Quezon City', 'draft', '2025-06-01', '2025-06-30', 1, NULL, 'Barangay Hall', NULL, NULL, 38000.00, 4, NULL),
(10, 'Flood Evacuation Awareness', 'Awareness program on flood evacuation routes and centers.', 'flood', 'Quezon City', 'draft', '2025-07-01', '2025-07-31', 1, NULL, 'IBP Road Junction', NULL, NULL, 42000.00, 4, NULL),
(11, 'Earthquake Go-Bag Campaign', 'Encouraging households to prepare earthquake go-bags.', 'earthquake', 'Quezon City', 'draft', '2025-08-01', '2025-08-31', 1, NULL, 'Holy Spirit Barangay Hall', NULL, NULL, 28000.00, 3, NULL),
(12, 'Youth Safety Seminar', 'Seminars focused on youth safety and disaster awareness.', 'youth', 'Quezon City', 'draft', '2025-09-01', '2025-09-15', 1, NULL, 'Youth Center', NULL, NULL, 25000.00, 3, NULL),
(13, 'Fire Extinguisher Training', 'Hands-on training on using fire extinguishers.', 'fire_safety', 'Quezon City', 'draft', '2025-10-01', '2025-10-15', 1, NULL, 'Commonwealth Covered Court', NULL, NULL, 32000.00, 4, NULL),
(14, 'Traffic Safety Awareness', 'Traffic safety and pedestrian discipline campaign.', 'road_safety', 'Quezon City', 'draft', '2025-10-16', '2025-10-31', 1, NULL, 'Phase 8 Covered Court', NULL, NULL, 28000.00, 3, NULL),
(15, 'Senior Citizen Safety Talk', 'Safety talks and orientations for senior citizens.', 'senior_safety', 'Quezon City', 'draft', '2025-11-01', '2025-11-15', 1, NULL, 'Senior Citizens Center', NULL, NULL, 20000.00, 2, NULL),
(16, 'Community Disaster Orientation', 'Community-wide disaster preparedness orientation.', 'disaster_preparedness', 'Quezon City', 'draft', '2025-11-16', '2025-11-30', 1, NULL, 'Bagong Silangan MRF', NULL, NULL, 50000.00, 6, NULL),
(17, 'School-Based Fire Safety', 'Fire safety activities focused on schools.', 'fire_safety', 'Quezon City', 'draft', '2025-12-01', '2025-12-15', 1, NULL, 'Holy Spirit Elementary School', NULL, NULL, 30000.00, 3, NULL),
(18, 'Typhoon Preparedness Campaign', 'Campaign on typhoon preparedness and early warning.', 'typhoon', 'Quezon City', 'draft', '2025-12-16', '2025-12-31', 1, NULL, 'Evacuation Center', NULL, NULL, 55000.00, 5, NULL);

-- CAMPAIGN-AUDIENCE LINKS
INSERT IGNORE INTO `campaign_department_campaign_audience` (campaign_id, segment_id) VALUES
(1, 1),
(1, 4),
(2, 2),
(2, 1),
(3, 1),
(3, 3);

-- EVENTS
INSERT IGNORE INTO `campaign_department_events` (id, linked_campaign_id, event_name, event_title, event_type, event_description, description, date, event_date, start_time, event_time, location, venue, facilitators, attendance_count, event_status, status, starts_at) VALUES
(1, 1, 'Fire Safety Seminar - Day 1', 'Fire Safety Seminar - Day 1', 'seminar', 'Introduction to fire safety and prevention', 'Introduction to fire safety and prevention', '2025-03-01', '2025-03-01', '09:00:00', '09:00:00', 'Barangay Hall - Barangay 1', 'Main Hall', '["John Doe", "Jane Smith"]', 45, 'completed', 'completed', '2025-03-01 09:00:00'),
(2, 1, 'Fire Drill Practice', 'Fire Drill Practice', 'drill', 'Hands-on fire drill practice session', 'Hands-on fire drill practice session', '2025-03-03', '2025-03-03', '14:00:00', '14:00:00', 'Barangay Hall - Barangay 1', 'Outdoor Area', '["Bob Johnson"]', 60, 'completed', 'completed', '2025-03-03 14:00:00'),
(3, 2, 'Earthquake Preparedness Workshop', 'Earthquake Preparedness Workshop', 'workshop', 'Interactive workshop on earthquake response', 'Interactive workshop on earthquake response', '2025-04-15', '2025-04-15', '10:00:00', '10:00:00', 'Quezon City Convention Center', 'Conference Room A', '["Maria Santos", "Pedro Reyes"]', 0, 'scheduled', 'scheduled', '2025-04-15 10:00:00');

-- CONTENT ITEMS
INSERT IGNORE INTO `campaign_department_content_items` (id, campaign_id, title, body, content_type, hazard_category, intended_audience_segment, source, approval_status, file_path, created_by) VALUES
(1, 1, 'Fire Safety Tips Poster', 'Essential fire safety tips for home', 'image', 'fire', 'general_public', 'Barangay Safety Office', 'approved', '/uploads/posters/fire_safety_tips.jpg', 1),
(2, 1, 'Fire Evacuation Plan', 'Step-by-step fire evacuation procedures', 'file', 'fire', 'residential', 'Fire Department', 'approved', '/uploads/plans/evacuation_plan.pdf', 1),
(3, 2, 'Earthquake Safety Video', 'Educational video on earthquake preparedness', 'video', 'earthquake', 'general_public', 'NDRRMC', 'pending', '/uploads/videos/earthquake_safety.mp4', 1),
(4, NULL, 'Fire Safety Poster', 'Poster for basic fire safety reminders.', 'image', 'fire', 'general_public', 'Seed Data', 'approved', '/uploads/materials/fire_safety_poster.jpg', 1),
(5, NULL, 'Flood Preparedness Checklist', 'Checklist for household flood preparedness.', 'file', 'flood', 'general_public', 'Seed Data', 'approved', '/uploads/materials/flood_preparedness_checklist.pdf', 1),
(6, NULL, 'Earthquake Drill Video', 'Video demonstrating earthquake drill procedures.', 'video', 'earthquake', 'general_public', 'Seed Data', 'approved', '/uploads/materials/earthquake_drill_video.mp4', 1),
(7, NULL, 'Emergency Go-Bag Guide', 'Guide on preparing emergency go-bags.', 'file', 'emergency', 'general_public', 'Seed Data', 'approved', '/uploads/materials/emergency_go_bag_guide.pdf', 1),
(8, NULL, 'Road Safety Infographic', 'Infographic about road and traffic safety.', 'image', 'traffic', 'general_public', 'Seed Data', 'approved', '/uploads/materials/road_safety_infographic.png', 1),
(9, NULL, 'Dengue Prevention Poster', 'Poster about dengue prevention measures.', 'image', 'health', 'general_public', 'Seed Data', 'approved', '/uploads/materials/dengue_prevention_poster.jpg', 1),
(10, NULL, 'Fire Drill Guide', 'Step-by-step fire drill guide.', 'file', 'fire', 'general_public', 'Seed Data', 'approved', '/uploads/materials/fire_drill_guide.pdf', 1),
(11, NULL, 'Typhoon Safety Video', 'Video on typhoon safety and preparedness.', 'video', 'typhoon', 'general_public', 'Seed Data', 'approved', '/uploads/materials/typhoon_safety_video.mp4', 1),
(12, NULL, 'Senior Citizen Safety Guide', 'Safety guide tailored for senior citizens.', 'file', 'senior', 'seniors', 'Seed Data', 'approved', '/uploads/materials/senior_citizen_safety_guide.pdf', 1),
(13, NULL, 'Youth Disaster Awareness Poster', 'Poster to raise disaster awareness among youth.', 'image', 'youth', 'youth', 'Seed Data', 'approved', '/uploads/materials/youth_disaster_awareness_poster.jpg', 1),
(14, NULL, 'Community Evacuation Map', 'Map showing community evacuation routes.', 'image', 'evacuation', 'general_public', 'Seed Data', 'approved', '/uploads/materials/community_evacuation_map.png', 1),
(15, NULL, 'Flood Risk Awareness Video', 'Video about flood risks and mitigation.', 'video', 'flood', 'general_public', 'Seed Data', 'approved', '/uploads/materials/flood_risk_awareness_video.mp4', 1),
(16, NULL, 'Fire Extinguisher Manual', 'Manual for using fire extinguishers.', 'file', 'equipment', 'general_public', 'Seed Data', 'approved', '/uploads/materials/fire_extinguisher_manual.pdf', 1),
(17, NULL, 'School Safety Checklist', 'Checklist for school-based safety checks.', 'file', 'school', 'school_community', 'Seed Data', 'approved', '/uploads/materials/school_safety_checklist.pdf', 1),
(18, NULL, 'Barangay Emergency Contacts', 'List of important barangay emergency contacts.', 'file', 'contacts', 'general_public', 'Seed Data', 'approved', '/uploads/materials/barangay_emergency_contacts.pdf', 1);

INSERT IGNORE INTO `campaign_department_content_items` (id, campaign_id, title, body, content_type, hazard_category, intended_audience_segment, source, approval_status, version_number, approved_by, approval_notes, file_path, file_reference, date_uploaded, visibility, created_by, created_at, updated_at) VALUES
(101, NULL, 'Fire Safety Tips for Households', 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers. Designed for residential areas.', 'poster', 'fire', 'households, residential areas', 'barangay-created', 'approved', 2, 1, 'Approved for distribution. Updated version with new contact numbers.', 'uploads/content_repository/fire_safety_households_v2.jpg', 'uploads/content_repository/fire_safety_households_v2.jpg', '2025-01-15 10:30:00', 'public', 1, '2025-01-10 09:00:00', '2025-01-15 10:30:00'),
(102, NULL, 'Flood Preparedness Checklist', 'Comprehensive checklist for flood preparedness including evacuation planning, emergency kit preparation, and post-flood safety measures.', 'guideline', 'flood', 'flood-prone areas, households', 'inspection-based', 'approved', 1, 1, 'Based on recent flood risk assessments in low-lying barangays.', 'uploads/content_repository/flood_preparedness_checklist.pdf', 'uploads/content_repository/flood_preparedness_checklist.pdf', '2025-01-20 14:15:00', 'public', 2, '2025-01-20 14:15:00', '2025-01-20 14:15:00'),
(103, NULL, 'Earthquake Safety: Drop, Cover, and Hold On', 'Educational video demonstrating proper earthquake response procedures. Includes animated demonstrations and real-world examples.', 'video', 'earthquake', 'general public, schools, workplaces', 'training-based', 'approved', 1, 1, 'High-quality educational content. Suitable for all age groups.', 'uploads/content_repository/earthquake_safety_video.mp4', 'uploads/content_repository/earthquake_safety_video.mp4', '2025-01-25 11:00:00', 'public', 1, '2025-01-25 11:00:00', '2025-01-25 11:00:00'),
(104, NULL, 'Typhoon Preparedness: Before, During, and After', 'Visual infographic showing typhoon preparedness steps, evacuation routes, and emergency contacts. Easy to understand format.', 'infographic', 'typhoon', 'coastal areas, general public', 'barangay-created', 'approved', 1, 1, 'Clear and visually appealing. Ready for social media distribution.', 'uploads/content_repository/typhoon_preparedness_infographic.png', 'uploads/content_repository/typhoon_preparedness_infographic.png', '2025-02-01 09:30:00', 'public', 2, '2025-02-01 09:30:00', '2025-02-01 09:30:00'),
(105, NULL, 'Health Safety Tips for Senior Citizens', 'Poster focusing on health safety measures for senior citizens including medication management, fall prevention, and emergency contacts.', 'poster', 'health', 'senior citizens, elderly', 'barangay-created', 'approved', 1, 1, 'Targeted content for vulnerable population. Approved for distribution.', 'uploads/content_repository/health_safety_seniors.jpg', 'uploads/content_repository/health_safety_seniors.jpg', '2025-02-05 13:20:00', 'public', 1, '2025-02-05 13:20:00', '2025-02-05 13:20:00'),
(106, NULL, 'Fire Safety for Schools', 'Fire safety guidelines specifically designed for school environments. Includes evacuation procedures and fire drill protocols.', 'guideline', 'fire', 'schools, students, teachers', 'training-based', 'pending', 1, NULL, NULL, 'uploads/content_repository/fire_safety_schools.pdf', 'uploads/content_repository/fire_safety_schools.pdf', '2025-02-10 10:00:00', 'internal', 2, '2025-02-10 10:00:00', '2025-02-10 10:00:00'),
(107, NULL, 'Dengue Prevention Infographic', 'Infographic showing dengue prevention measures including mosquito breeding prevention and symptoms recognition.', 'infographic', 'health', 'general public, households', 'inspection-based', 'pending', 1, NULL, NULL, 'uploads/content_repository/dengue_prevention.png', 'uploads/content_repository/dengue_prevention.png', '2025-02-12 15:45:00', 'public', 1, '2025-02-12 15:45:00', '2025-02-12 15:45:00'),
(108, NULL, 'Earthquake Preparedness for High-Rise Buildings', 'Guidelines for earthquake preparedness in high-rise residential and commercial buildings. Currently being reviewed.', 'guideline', 'earthquake', 'residential buildings, commercial buildings', 'inspection-based', 'draft', 1, NULL, NULL, 'uploads/content_repository/earthquake_highrise_draft.pdf', 'uploads/content_repository/earthquake_highrise_draft.pdf', '2025-02-15 11:30:00', 'internal', 2, '2025-02-15 11:30:00', '2025-02-15 11:30:00'),
(109, NULL, 'Youth Safety Awareness Video', 'Video targeting youth on various safety topics including road safety, online safety, and emergency response.', 'video', 'health', 'youth, teenagers, students', 'barangay-created', 'draft', 1, NULL, NULL, 'uploads/content_repository/youth_safety_video_draft.mp4', 'uploads/content_repository/youth_safety_video_draft.mp4', '2025-02-18 09:15:00', 'internal', 1, '2025-02-18 09:15:00', '2025-02-18 09:15:00'),
(110, NULL, 'Flood Safety Poster (Rejected)', 'Initial version of flood safety poster that was rejected due to outdated information and unclear messaging.', 'poster', 'flood', 'general public', 'barangay-created', 'rejected', 1, 1, 'Rejected: Contains outdated contact numbers and unclear evacuation instructions. Please revise and resubmit.', 'uploads/content_repository/flood_safety_rejected.jpg', 'uploads/content_repository/flood_safety_rejected.jpg', '2025-01-28 14:00:00', 'internal', 2, '2025-01-28 14:00:00', '2025-01-30 10:00:00'),
(111, NULL, 'Emergency Contact Numbers Quick Reference', 'Quick reference card with all emergency contact numbers including fire, police, medical, and barangay hotlines.', 'poster', 'emergency', 'general public, households', 'barangay-created', 'approved', 1, 1, 'Essential reference material. Approved for wide distribution.', 'uploads/content_repository/emergency_contacts.jpg', 'uploads/content_repository/emergency_contacts.jpg', '2025-02-08 10:00:00', 'public', 1, '2025-02-08 10:00:00', '2025-02-08 10:00:00'),
(112, NULL, 'First Aid Basics Video', 'Basic first aid procedures video covering CPR, wound care, and common emergency responses.', 'video', 'health', 'general public, community volunteers', 'training-based', 'approved', 1, 1, 'Excellent training material. Suitable for community workshops.', 'uploads/content_repository/first_aid_basics.mp4', 'uploads/content_repository/first_aid_basics.mp4', '2025-02-14 16:30:00', 'public', 2, '2025-02-14 16:30:00', '2025-02-14 16:30:00');

-- Fix content items with version_number column issue
UPDATE `campaign_department_content_items` SET version_number = 1 WHERE version_number = 0 OR version_number IS NULL;

-- CONTENT ITEM VERSIONS
INSERT IGNORE INTO `campaign_department_content_item_versions` (content_id, version_number, title, body, file_reference, file_path, changed_by, change_notes, created_at) VALUES
(101, 1, 'Fire Safety Tips for Households', 'Essential fire safety tips including smoke detector maintenance and kitchen safety.', 'uploads/content_repository/fire_safety_households_v1.jpg', 'uploads/content_repository/fire_safety_households_v1.jpg', 1, 'Initial version uploaded', '2025-01-10 09:00:00'),
(101, 2, 'Fire Safety Tips for Households', 'Essential fire safety tips including smoke detector maintenance, kitchen safety, and emergency contact numbers.', 'uploads/content_repository/fire_safety_households_v2.jpg', 'uploads/content_repository/fire_safety_households_v2.jpg', 1, 'Updated with new emergency contact numbers and improved layout', '2025-01-15 10:30:00');

-- ATTACHMENTS
INSERT IGNORE INTO `campaign_department_attachments` (content_item_id, file_path, mime_type, file_size) VALUES
(101, 'uploads/content_repository/fire_safety_households_v2.jpg', 'image/jpeg', 245760),
(102, 'uploads/content_repository/flood_preparedness_checklist.pdf', 'application/pdf', 512000),
(103, 'uploads/content_repository/earthquake_safety_video.mp4', 'video/mp4', 5242880),
(104, 'uploads/content_repository/typhoon_preparedness_infographic.png', 'image/png', 384000),
(105, 'uploads/content_repository/health_safety_seniors.jpg', 'image/jpeg', 198656),
(106, 'uploads/content_repository/fire_safety_schools.pdf', 'application/pdf', 456704),
(107, 'uploads/content_repository/dengue_prevention.png', 'image/png', 320000),
(108, 'uploads/content_repository/earthquake_highrise_draft.pdf', 'application/pdf', 678912),
(109, 'uploads/content_repository/youth_safety_video_draft.mp4', 'video/mp4', 8388608),
(110, 'uploads/content_repository/flood_safety_rejected.jpg', 'image/jpeg', 215040),
(111, 'uploads/content_repository/emergency_contacts.jpg', 'image/jpeg', 153600),
(112, 'uploads/content_repository/first_aid_basics.mp4', 'video/mp4', 6291456);

-- TAGS
INSERT IGNORE INTO `campaign_department_tags` (id, name) VALUES
(101, 'fire-safety'),
(102, 'flood-preparedness'),
(103, 'earthquake'),
(104, 'typhoon'),
(105, 'health'),
(106, 'emergency'),
(107, 'senior-citizens'),
(108, 'schools'),
(109, 'households'),
(110, 'youth');

-- CONTENT TAGS
INSERT IGNORE INTO `campaign_department_content_tags` (content_item_id, tag_id) VALUES
(101, 101), (101, 109),
(102, 102), (102, 109),
(103, 103), (103, 108),
(104, 104),
(105, 105), (105, 107),
(106, 101), (106, 108),
(107, 105), (107, 109),
(108, 103),
(109, 105), (109, 110),
(110, 102), (110, 109),
(111, 106),
(112, 105), (112, 106);

-- CAMPAIGN-CONTENT LINKING
INSERT IGNORE INTO `campaign_department_campaign_content_items` (campaign_id, content_id, attached_by, attached_at) VALUES
(1, 101, 1, '2025-02-20 10:00:00'),
(1, 111, 1, '2025-02-20 10:05:00'),
(3, 102, 2, '2025-02-21 14:00:00'),
(2, 103, 1, '2025-02-22 09:30:00');

-- CONTENT USAGE
INSERT IGNORE INTO `campaign_department_content_usage` (content_item_id, usage_context) VALUES
(101, 'Distributed during Fire Safety Awareness Week'),
(102, 'Included in flood preparedness information packets'),
(103, 'Screened during earthquake preparedness seminar'),
(111, 'Distributed as quick reference cards');

-- SURVEYS
INSERT IGNORE INTO `campaign_department_surveys` (id, campaign_id, title, description) VALUES
(1, 1, 'Fire Safety Awareness Survey', 'Survey to measure fire safety awareness after campaign'),
(2, 2, 'Earthquake Preparedness Assessment', 'Assessment of earthquake preparedness knowledge');

-- SURVEY QUESTIONS
INSERT IGNORE INTO `campaign_department_survey_questions` (id, survey_id, question_text, question_type, options_json) VALUES
(1, 1, 'How would you rate your fire safety knowledge before this campaign?', 'rating', NULL),
(2, 1, 'How would you rate your fire safety knowledge after this campaign?', 'rating', NULL),
(3, 1, 'What fire safety measures do you have at home?', 'multiple_choice', '["smoke_detector", "fire_extinguisher", "fire_blanket", "evacuation_plan", "none"]'),
(4, 2, 'Do you know the proper "Drop, Cover, and Hold On" procedure?', 'single_choice', '["yes", "no", "unsure"]'),
(5, 2, 'How prepared do you feel for an earthquake?', 'rating', NULL);

-- FEEDBACK
INSERT IGNORE INTO `campaign_department_feedback` (feedback_id, survey_id, rating, comment, submitted_at) VALUES
(1, 1, 5, 'Very informative campaign! Learned a lot about fire safety.', '2025-03-05 10:30:00'),
(2, 1, 4, 'Good information, but could use more practical demonstrations.', '2025-03-05 11:15:00'),
(3, 1, 5, 'Excellent! The fire drill was very helpful.', '2025-03-05 14:20:00'),
(4, 2, 4, 'The workshop was informative.', '2025-04-15 12:00:00');

-- PARTNER ENGAGEMENTS
INSERT IGNORE INTO `campaign_department_partner_engagements` (id, partner_id, campaign_id, engagement_type, notes) VALUES
(1, 1, 1, 'co_host', 'School provided venue for fire safety seminar'),
(2, 2, 1, 'resource_sharing', 'Red Cross provided first aid training materials'),
(3, 3, 2, 'co_host', 'High school hosted earthquake preparedness workshop'),
(4, 4, 3, 'coordination', 'NGO assisting with flood preparedness materials distribution');

-- REFERENCE LOCATIONS
INSERT IGNORE INTO `campaign_department_reference_locations` (name, barangay_name, city) VALUES
('Commonwealth Covered Court', 'Commonwealth', 'Quezon City'),
('Phase 8 Covered Court', 'Commonwealth', 'Quezon City'),
('IBP Road Area', 'Batasan Hills', 'Quezon City'),
('Litex Area', 'Batasan Hills', 'Quezon City'),
('Payatas Gymnasium', 'Payatas', 'Quezon City'),
('Payatas Elementary School', 'Payatas', 'Quezon City'),
('Holy Spirit Barangay Hall', 'Holy Spirit', 'Quezon City'),
('Holy Spirit Elementary School', 'Holy Spirit', 'Quezon City'),
('Bagong Silangan MRF', 'Bagong Silangan', 'Quezon City'),
('Bagong Silangan Multi-Purpose Hall', 'Bagong Silangan', 'Quezon City'),
('Youth Center', 'Holy Spirit', 'Quezon City'),
('Senior Citizens Center', 'Payatas', 'Quezon City'),
('Evacuation Center', 'Commonwealth', 'Quezon City'),
('IBP Road Junction', 'Batasan Hills', 'Quezon City'),
('Barangay Hall', 'Commonwealth', 'Quezon City');

-- REFERENCE STAFF
INSERT IGNORE INTO `campaign_department_reference_staff` (name, role) VALUES
('Juan Dela Cruz', 'Barangay Safety Officer'),
('Maria Santos', 'DRRM Coordinator'),
('Pedro Reyes', 'Fire Safety Marshal'),
('Ana Lopez', 'Campaign Officer'),
('Mark Villanueva', 'Traffic Safety Officer'),
('Liza Mendoza', 'Community Organizer'),
('Carlos Ramirez', 'Disaster Preparedness Trainer'),
('Grace Flores', 'Health & Safety Officer'),
('Joseph Lim', 'Barangay Secretary'),
('Noel Bautista', 'Logistics Coordinator'),
('Rhea Cruz', 'Youth Coordinator'),
('Alvin Garcia', 'IT Support'),
('Michelle Tan', 'NGO Liaison'),
('Robert Aquino', 'Seminar Facilitator'),
('Karen Dizon', 'Data Analyst');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

