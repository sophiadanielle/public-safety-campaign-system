-- Feedback and Survey Tools Module - Complete Requirements Implementation
-- Migration 027: Full survey module with all required fields and capabilities
-- System: Public Safety Campaign Management System
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. ENHANCE SURVEYS TABLE
-- ============================================

-- Add survey_status enum (draft, published, closed)
ALTER TABLE `campaign_department_surveys`
    MODIFY COLUMN status ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft';

-- Add published_via field
ALTER TABLE `campaign_department_surveys`
    ADD COLUMN IF NOT EXISTS published_via ENUM('link', 'qr_code', 'both') NULL AFTER status;

-- Add linked_event_id if not exists (from migration 009)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'campaign_department_surveys' 
    AND COLUMN_NAME = 'event_id');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `campaign_department_surveys` ADD COLUMN event_id INT UNSIGNED NULL AFTER campaign_id',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add audit fields
ALTER TABLE `campaign_department_surveys`
    ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER published_via,
    ADD COLUMN IF NOT EXISTS published_by INT UNSIGNED NULL AFTER created_by,
    ADD COLUMN IF NOT EXISTS published_at TIMESTAMP NULL AFTER published_by,
    ADD COLUMN IF NOT EXISTS closed_at TIMESTAMP NULL AFTER published_at,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER closed_at;

-- Add foreign keys for audit fields (MariaDB compatible - check first)
SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_surveys' AND CONSTRAINT_NAME = 'fk_surveys_created_by');
SET @sql = IF(@constraint_exists = 0, 'ALTER TABLE `campaign_department_surveys` ADD CONSTRAINT fk_surveys_created_by FOREIGN KEY (created_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'campaign_department_surveys' AND CONSTRAINT_NAME = 'fk_surveys_published_by');
SET @sql = IF(@constraint_exists = 0, 'ALTER TABLE `campaign_department_surveys` ADD CONSTRAINT fk_surveys_published_by FOREIGN KEY (published_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. ENHANCE SURVEY_QUESTIONS TABLE
-- ============================================

-- Add question_order and required_flag
ALTER TABLE `campaign_department_survey_questions`
    ADD COLUMN IF NOT EXISTS question_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER survey_id,
    ADD COLUMN IF NOT EXISTS required_flag BOOLEAN NOT NULL DEFAULT FALSE AFTER question_order;

-- Update question_type enum to match requirements
ALTER TABLE `campaign_department_survey_questions`
    MODIFY COLUMN question_type ENUM('rating', 'multiple_choice', 'yes_no', 'open_ended', 'text', 'single_choice') NOT NULL DEFAULT 'open_ended';

-- Add index for ordering
CREATE INDEX IF NOT EXISTS idx_survey_questions_order ON `campaign_department_survey_questions`(survey_id, question_order);

-- ============================================
-- 3. ENHANCE SURVEY_RESPONSES TABLE
-- ============================================

-- Add respondent_identifier field
ALTER TABLE `campaign_department_survey_responses`
    ADD COLUMN IF NOT EXISTS respondent_identifier VARCHAR(255) NULL AFTER survey_id;

-- Rename submitted_at to submission_timestamp for consistency
ALTER TABLE `campaign_department_survey_responses`
    CHANGE COLUMN submitted_at submission_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Add index for respondent lookup
CREATE INDEX IF NOT EXISTS idx_survey_responses_respondent ON `campaign_department_survey_responses`(respondent_identifier);

-- ============================================
-- 4. CREATE SURVEY_RESPONSES_DETAIL TABLE
-- ============================================
-- Individual question responses for better querying and aggregation

CREATE TABLE IF NOT EXISTS `campaign_department_survey_response_details` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    response_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    response_value TEXT NOT NULL COMMENT 'Stores the actual response (rating number, selected option, text, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_response_details_response FOREIGN KEY (response_id) REFERENCES `campaign_department_survey_responses`(id) ON DELETE CASCADE,
    CONSTRAINT fk_response_details_question FOREIGN KEY (question_id) REFERENCES `campaign_department_survey_questions`(id) ON DELETE CASCADE,
    INDEX idx_response_details_response (response_id),
    INDEX idx_response_details_question (question_id),
    UNIQUE KEY uk_response_details (response_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CREATE SURVEY_AGGREGATED_RESULTS TABLE
-- ============================================
-- Pre-computed aggregated results for performance

CREATE TABLE IF NOT EXISTS `campaign_department_survey_aggregated_results` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    average_rating DECIMAL(5,2) NULL COMMENT 'For rating questions',
    response_distribution JSON NULL COMMENT 'Distribution of responses (e.g., {"Yes": 10, "No": 5})',
    total_responses INT UNSIGNED NOT NULL DEFAULT 0,
    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_aggregated_results_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE,
    CONSTRAINT fk_aggregated_results_question FOREIGN KEY (question_id) REFERENCES `campaign_department_survey_questions`(id) ON DELETE CASCADE,
    UNIQUE KEY uk_aggregated_results (survey_id, question_id),
    INDEX idx_aggregated_results_survey (survey_id),
    INDEX idx_aggregated_results_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CREATE SURVEY_AUDIT_LOG TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS `campaign_department_survey_audit_log` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action_type ENUM('created', 'updated', 'published', 'closed', 'question_added', 'question_updated', 'question_deleted', 'response_submitted') NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    change_details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_survey_audit_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_audit_user FOREIGN KEY (user_id) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL,
    INDEX idx_survey_audit_survey (survey_id),
    INDEX idx_survey_audit_user (user_id),
    INDEX idx_survey_audit_action (action_type),
    INDEX idx_survey_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CREATE SURVEY_INTEGRATION_CHECKPOINTS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS `campaign_department_survey_integration_checkpoints` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    subsystem_type ENUM('event_management', 'disaster_preparedness', 'community_policing', 'emergency_response') NOT NULL,
    integration_status ENUM('pending', 'sent', 'acknowledged', 'confirmed', 'failed') NOT NULL DEFAULT 'pending',
    sent_data JSON NULL,
    received_data JSON NULL,
    last_sync_at TIMESTAMP NULL,
    sync_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_survey_integration_survey FOREIGN KEY (survey_id) REFERENCES `campaign_department_surveys`(id) ON DELETE CASCADE,
    INDEX idx_survey_integration_survey (survey_id),
    INDEX idx_survey_integration_subsystem (subsystem_type),
    INDEX idx_survey_integration_status (integration_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. ADD INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX IF NOT EXISTS idx_surveys_status ON `campaign_department_surveys`(status);
CREATE INDEX IF NOT EXISTS idx_surveys_campaign ON `campaign_department_surveys`(campaign_id);
CREATE INDEX IF NOT EXISTS idx_surveys_event ON `campaign_department_surveys`(event_id);
CREATE INDEX IF NOT EXISTS idx_surveys_created_by ON `campaign_department_surveys`(created_by);
CREATE INDEX IF NOT EXISTS idx_survey_responses_survey ON `campaign_department_survey_responses`(survey_id);
CREATE INDEX IF NOT EXISTS idx_survey_responses_timestamp ON `campaign_department_survey_responses`(submission_timestamp);

-- ============================================
-- 9. CREATE VIEW FOR SURVEY SUMMARY
-- ============================================

CREATE OR REPLACE VIEW `campaign_department_survey_summary_view` AS
SELECT 
    s.id AS survey_id,
    s.title AS survey_title,
    s.description AS survey_description,
    s.status AS survey_status,
    s.published_via,
    s.campaign_id,
    s.event_id,
    s.created_by,
    s.published_by,
    s.published_at,
    s.closed_at,
    s.created_at,
    s.updated_at,
    COUNT(DISTINCT sq.id) AS question_count,
    COUNT(DISTINCT sr.id) AS total_responses,
    COUNT(DISTINCT CASE WHEN sr.submission_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN sr.id END) AS responses_last_7_days,
    MAX(sr.submission_timestamp) AS last_response_at
FROM `campaign_department_surveys` s
LEFT JOIN `campaign_department_survey_questions` sq ON sq.survey_id = s.id
LEFT JOIN `campaign_department_survey_responses` sr ON sr.survey_id = s.id
GROUP BY s.id, s.title, s.description, s.status, s.published_via, s.campaign_id, s.event_id, 
         s.created_by, s.published_by, s.published_at, s.closed_at, s.created_at, s.updated_at;

-- ============================================
-- 10. UPDATE EXISTING DATA
-- ============================================

-- Set question_order based on current id order
UPDATE `campaign_department_survey_questions` sq
SET question_order = (
    SELECT COUNT(*) 
    FROM `campaign_department_survey_questions` sq2 
    WHERE sq2.survey_id = sq.survey_id AND sq2.id <= sq.id
)
WHERE question_order = 0;

-- Set created_by for existing surveys (if not set)
UPDATE `campaign_department_surveys` s
SET s.created_by = (
    SELECT c.owner_id 
    FROM `campaign_department_campaigns` c 
    WHERE c.id = s.campaign_id 
    LIMIT 1
)
WHERE s.created_by IS NULL AND s.campaign_id IS NOT NULL;



