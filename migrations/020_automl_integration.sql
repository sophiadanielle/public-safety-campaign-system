-- Google AutoML Integration Module
-- Migration 020: Database schema for AI training, models, predictions, and caching
-- System: Public Safety Campaign Management Scheduler for Barangays in Quezon City
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. AI MODEL VERSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `campaign_department_ai_model_versions` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_id VARCHAR(255) NOT NULL COMMENT 'Google Vertex AI model ID',
    model_name VARCHAR(200) NOT NULL COMMENT 'Human-readable model name',
    model_type ENUM('schedule_optimization', 'conflict_prediction', 'engagement_prediction', 'readiness_forecast') NOT NULL,
    training_version VARCHAR(50) NOT NULL COMMENT 'Version tag (e.g., v1.0.0)',
    project_id VARCHAR(100) NULL COMMENT 'Google Cloud project ID',
    region VARCHAR(50) NULL COMMENT 'GCP region',
    dataset_id VARCHAR(255) NULL COMMENT 'Vertex AI dataset ID',
    training_job_id VARCHAR(255) NULL COMMENT 'Vertex AI training job ID',
    evaluation_metrics JSON NULL COMMENT 'Model evaluation metrics (accuracy, precision, recall, etc.)',
    feature_columns JSON NOT NULL COMMENT 'List of feature columns used in training',
    target_column VARCHAR(100) NOT NULL COMMENT 'Prediction target column name',
    training_data_size INT UNSIGNED NULL COMMENT 'Number of training examples',
    training_started_at DATETIME NULL,
    training_completed_at DATETIME NULL,
    training_status ENUM('pending', 'training', 'completed', 'failed', 'deployed', 'archived') NOT NULL DEFAULT 'pending',
    deployed_at DATETIME NULL COMMENT 'When model was deployed for production use',
    is_active BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Currently active model for this type',
    created_by INT UNSIGNED NULL COMMENT 'User who initiated training',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_model_type (model_type),
    INDEX idx_training_status (training_status),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),
    CONSTRAINT fk_ai_model_versions_created_by FOREIGN KEY (created_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Google AutoML model versions and training metadata';

-- ============================================
-- 2. AI TRAINING LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `campaign_department_ai_training_logs` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_version_id INT UNSIGNED NOT NULL,
    action_type ENUM('training_started', 'training_progress', 'training_completed', 'training_failed', 'model_deployed', 'model_archived') NOT NULL,
    message TEXT NOT NULL,
    metadata JSON NULL COMMENT 'Additional context (error details, progress percentage, etc.)',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_model_version_id (model_version_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_ai_training_logs_model_version FOREIGN KEY (model_version_id) REFERENCES `campaign_department_ai_model_versions`(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_training_logs_created_by FOREIGN KEY (created_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Training event logs for audit and debugging';

-- ============================================
-- 3. AI PREDICTION CACHE TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `campaign_department_ai_prediction_cache` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE COMMENT 'MD5 hash of feature payload',
    model_type ENUM('schedule_optimization', 'conflict_prediction', 'engagement_prediction', 'readiness_forecast') NOT NULL,
    model_version_id INT UNSIGNED NULL COMMENT 'Model version used for this prediction',
    entity_type ENUM('campaign', 'event', 'seminar') NOT NULL,
    entity_id INT UNSIGNED NOT NULL COMMENT 'Campaign ID, Event ID, etc.',
    feature_hash VARCHAR(64) NOT NULL COMMENT 'SHA256 of feature payload for cache invalidation',
    prediction_result JSON NOT NULL COMMENT 'Cached prediction result',
    confidence_score DECIMAL(5,3) NULL,
    expires_at DATETIME NOT NULL COMMENT 'Cache expiration timestamp',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_model_type (model_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_expires_at (expires_at),
    CONSTRAINT fk_ai_prediction_cache_model_version FOREIGN KEY (model_version_id) REFERENCES `campaign_department_ai_model_versions`(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Cached AI predictions to avoid redundant API calls';

-- ============================================
-- 4. AI PREDICTION REQUESTS LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `campaign_department_ai_prediction_requests` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_type ENUM('schedule_optimization', 'conflict_prediction', 'engagement_prediction', 'readiness_forecast') NOT NULL,
    entity_type ENUM('campaign', 'event', 'seminar') NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    model_version_id INT UNSIGNED NULL,
    request_payload JSON NOT NULL COMMENT 'Feature payload sent to AutoML',
    response_payload JSON NULL COMMENT 'Raw AutoML API response',
    prediction_result JSON NULL COMMENT 'Processed prediction result',
    used_cache BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether result came from cache',
    cache_key VARCHAR(255) NULL,
    response_time_ms INT UNSIGNED NULL COMMENT 'API response time in milliseconds',
    success BOOLEAN NOT NULL DEFAULT TRUE,
    error_message TEXT NULL,
    requested_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_model_type (model_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_requested_by (requested_by),
    INDEX idx_created_at (created_at),
    INDEX idx_success (success),
    CONSTRAINT fk_ai_prediction_requests_model_version FOREIGN KEY (model_version_id) REFERENCES `campaign_department_ai_model_versions`(id) ON DELETE SET NULL,
    CONSTRAINT fk_ai_prediction_requests_requested_by FOREIGN KEY (requested_by) REFERENCES `campaign_department_users`(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Audit log of all AI prediction requests';

-- ============================================
-- 5. ENHANCE EXISTING automl_predictions TABLE (if exists)
-- ============================================
-- Add model_version_id if table exists (using simple ALTER with error handling)
-- Note: MySQL doesn't support IF NOT EXISTS for ALTER TABLE, so we'll handle errors gracefully

-- ============================================
-- 6. CREATE INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX IF NOT EXISTS idx_ai_model_versions_deployed ON `campaign_department_ai_model_versions`(training_status, is_active, model_type);
CREATE INDEX IF NOT EXISTS idx_ai_prediction_cache_lookup ON `campaign_department_ai_prediction_cache`(model_type, entity_type, entity_id, expires_at);

-- ============================================
-- 7. INSERT DEFAULT MODEL VERSION RECORDS (Optional)
-- ============================================
-- These will be created when actual training is initiated

