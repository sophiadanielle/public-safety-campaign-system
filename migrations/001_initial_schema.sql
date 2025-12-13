-- Initial schema for Public Safety Campaign Platform
-- MySQL 8+ (InnoDB, utf8mb4)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE barangays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    city VARCHAR(150) NULL,
    province VARCHAR(150) NULL,
    region VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    barangay_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_users_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id)
) ENGINE=InnoDB;

CREATE TABLE campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    status ENUM('draft','scheduled','active','completed','archived') NOT NULL DEFAULT 'draft',
    start_date DATE NULL,
    end_date DATE NULL,
    owner_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaigns_owner FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE campaign_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    scheduled_at DATETIME NOT NULL,
    channel VARCHAR(100) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_schedules_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE content_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NULL,
    content_type ENUM('text','image','video','link','file') NOT NULL DEFAULT 'text',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_content_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attachments_content FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audience_segments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    criteria JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audience_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    segment_id INT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NULL,
    channel ENUM('sms','email','push','social','other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audience_members_segment FOREIGN KEY (segment_id) REFERENCES audience_segments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE campaign_audience (
    campaign_id INT UNSIGNED NOT NULL,
    segment_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (campaign_id, segment_id),
    CONSTRAINT fk_campaign_audience_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_audience_segment FOREIGN KEY (segment_id) REFERENCES audience_segments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    audience_member_id INT UNSIGNED NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_member FOREIGN KEY (audience_member_id) REFERENCES audience_members(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE surveys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_surveys_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE survey_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('text','single_choice','multiple_choice','rating') NOT NULL DEFAULT 'text',
    options_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_questions_survey FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE survey_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    survey_id INT UNSIGNED NOT NULL,
    audience_member_id INT UNSIGNED NULL,
    responses_json JSON NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_responses_survey FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    CONSTRAINT fk_responses_member FOREIGN KEY (audience_member_id) REFERENCES audience_members(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE impact_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    metric_name VARCHAR(150) NOT NULL,
    metric_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_metrics_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(150) NULL,
    contact_email VARCHAR(150) NULL,
    contact_phone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE partner_engagements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id INT UNSIGNED NOT NULL,
    campaign_id INT UNSIGNED NOT NULL,
    engagement_type VARCHAR(100) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_partner_engagements_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    CONSTRAINT fk_partner_engagements_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE automl_predictions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    prediction JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_predictions_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE integration_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(100) NOT NULL,
    payload JSON NULL,
    status ENUM('success','failed') NOT NULL DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE notification_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NULL,
    audience_member_id INT UNSIGNED NULL,
    channel VARCHAR(50) NOT NULL,
    status ENUM('sent','failed','queued') NOT NULL DEFAULT 'queued',
    response_message VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    CONSTRAINT fk_notifications_member FOREIGN KEY (audience_member_id) REFERENCES audience_members(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE content_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_item_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NULL,
    usage_context VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_content_usage_content FOREIGN KEY (content_item_id) REFERENCES content_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_usage_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB;


