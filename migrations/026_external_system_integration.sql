-- External System Integration Architecture
-- Migration 026: Comprehensive integration system for connecting submodules with external subsystems
-- System: Public Safety Campaign Management System
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================
-- 1. EXTERNAL SYSTEMS REGISTRY
-- ============================================
-- Stores configuration for each external subsystem
CREATE TABLE IF NOT EXISTS `campaign_department_external_systems` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Name of the external system (e.g., law_enforcement, traffic_transport)',
    system_type ENUM('database', 'api', 'hybrid') NOT NULL DEFAULT 'api' COMMENT 'Integration type',
    display_name VARCHAR(255) NULL COMMENT 'Human-readable name',
    description TEXT NULL COMMENT 'System description',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether integration is enabled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_system_name (system_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. EXTERNAL SYSTEM CONNECTION CONFIGURATIONS
-- ============================================
-- Stores connection details for external systems
CREATE TABLE IF NOT EXISTS `campaign_department_external_system_connections` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_id INT UNSIGNED NOT NULL,
    connection_type ENUM('database', 'api', 'rest', 'soap', 'graphql') NOT NULL,
    
    -- Database connection fields
    db_host VARCHAR(255) NULL,
    db_port INT UNSIGNED NULL DEFAULT 3306,
    db_name VARCHAR(100) NULL,
    db_username VARCHAR(100) NULL,
    db_password VARCHAR(255) NULL COMMENT 'Encrypted password',
    db_driver ENUM('mysql', 'postgresql', 'mssql', 'oracle') NULL DEFAULT 'mysql',
    
    -- API connection fields
    api_base_url VARCHAR(500) NULL,
    api_auth_type ENUM('bearer', 'basic', 'oauth2', 'apikey', 'none') NULL DEFAULT 'bearer',
    api_auth_token TEXT NULL COMMENT 'Encrypted token/credentials',
    api_timeout INT UNSIGNED NULL DEFAULT 30 COMMENT 'Request timeout in seconds',
    
    -- Additional configuration (JSON)
    config_json JSON NULL COMMENT 'Additional connection parameters',
    
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_connection_test TIMESTAMP NULL COMMENT 'Last successful connection test',
    connection_status ENUM('connected', 'disconnected', 'error', 'never_tested') NOT NULL DEFAULT 'never_tested',
    connection_error TEXT NULL COMMENT 'Last connection error message',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_external_system_connection FOREIGN KEY (system_id) REFERENCES `campaign_department_external_systems`(id) ON DELETE CASCADE,
    INDEX idx_system_id (system_id),
    INDEX idx_connection_type (connection_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. DATA MAPPING CONFIGURATIONS
-- ============================================
-- Maps external system data to this system's schema
CREATE TABLE IF NOT EXISTS `campaign_department_external_data_mappings` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_id INT UNSIGNED NOT NULL,
    mapping_name VARCHAR(100) NOT NULL COMMENT 'Name of this mapping (e.g., incidents_to_events)',
    source_table VARCHAR(100) NULL COMMENT 'External system table/endpoint',
    target_table VARCHAR(100) NOT NULL COMMENT 'This system table',
    mapping_config JSON NOT NULL COMMENT 'Field mappings and transformation rules',
    sync_frequency ENUM('realtime', 'hourly', 'daily', 'weekly', 'manual') NOT NULL DEFAULT 'manual',
    last_sync_at TIMESTAMP NULL,
    next_sync_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_external_data_mapping_system FOREIGN KEY (system_id) REFERENCES `campaign_department_external_systems`(id) ON DELETE CASCADE,
    INDEX idx_system_id (system_id),
    INDEX idx_target_table (target_table),
    INDEX idx_mapping_name (mapping_name),
    INDEX idx_next_sync_at (next_sync_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. SYNCED DATA CACHE
-- ============================================
-- Caches data synced from external systems
CREATE TABLE IF NOT EXISTS `campaign_department_external_data_cache` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_id INT UNSIGNED NOT NULL,
    mapping_id INT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NOT NULL COMMENT 'ID from external system',
    local_id INT UNSIGNED NULL COMMENT 'ID in this system (if mapped)',
    data_json JSON NOT NULL COMMENT 'Cached data from external system',
    sync_status ENUM('pending', 'synced', 'error', 'outdated') NOT NULL DEFAULT 'pending',
    sync_error TEXT NULL,
    last_synced_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL COMMENT 'When this cache entry expires',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_external_data_cache_system FOREIGN KEY (system_id) REFERENCES `campaign_department_external_systems`(id) ON DELETE CASCADE,
    CONSTRAINT fk_external_data_cache_mapping FOREIGN KEY (mapping_id) REFERENCES `campaign_department_external_data_mappings`(id) ON DELETE CASCADE,
    INDEX idx_system_id (system_id),
    INDEX idx_mapping_id (mapping_id),
    INDEX idx_external_id (external_id),
    INDEX idx_local_id (local_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_expires_at (expires_at),
    UNIQUE KEY uk_system_external_id (system_id, external_id, mapping_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. INTEGRATION QUERY LOGS
-- ============================================
-- Logs all queries to external systems
CREATE TABLE IF NOT EXISTS `campaign_department_integration_query_logs` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_id INT UNSIGNED NOT NULL,
    query_type ENUM('select', 'insert', 'update', 'delete', 'api_get', 'api_post', 'api_put', 'api_delete') NOT NULL,
    query_string TEXT NULL COMMENT 'SQL query or API endpoint',
    request_payload JSON NULL COMMENT 'Request data',
    response_payload JSON NULL COMMENT 'Response data',
    response_status INT UNSIGNED NULL COMMENT 'HTTP status code or SQL affected rows',
    execution_time_ms INT UNSIGNED NULL COMMENT 'Query execution time in milliseconds',
    status ENUM('success', 'error', 'timeout') NOT NULL,
    error_message TEXT NULL,
    requested_by INT UNSIGNED NULL COMMENT 'User ID who requested this query',
    module_name VARCHAR(100) NULL COMMENT 'Which submodule requested this (campaigns, events, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_integration_query_log_system FOREIGN KEY (system_id) REFERENCES `campaign_department_external_systems`(id) ON DELETE CASCADE,
    INDEX idx_system_id (system_id),
    INDEX idx_query_type (query_type),
    INDEX idx_status (status),
    INDEX idx_module_name (module_name),
    INDEX idx_created_at (created_at),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. MODULE-TO-SYSTEM MAPPINGS
-- ============================================
-- Maps which external systems each submodule can query
CREATE TABLE IF NOT EXISTS `campaign_department_module_system_mappings` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL COMMENT 'Submodule name (campaigns, events, segments, etc.)',
    system_id INT UNSIGNED NOT NULL,
    access_type ENUM('read', 'write', 'read_write') NOT NULL DEFAULT 'read',
    query_permissions JSON NULL COMMENT 'Allowed queries/endpoints for this module',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_module_system_mapping_system FOREIGN KEY (system_id) REFERENCES `campaign_department_external_systems`(id) ON DELETE CASCADE,
    INDEX idx_module_name (module_name),
    INDEX idx_system_id (system_id),
    UNIQUE KEY uk_module_system (module_name, system_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. INSERT DEFAULT EXTERNAL SYSTEMS
-- ============================================
-- Pre-populate with known subsystems
INSERT INTO `campaign_department_external_systems` (system_name, display_name, system_type, description) VALUES
('law_enforcement', 'Law Enforcement & Incident Reporting', 'hybrid', 'Police reports, incidents, and law enforcement data'),
('traffic_transport', 'Traffic & Transport Management', 'hybrid', 'Traffic incidents, transport schedules, and road conditions'),
('fire_rescue', 'Fire & Rescue Services', 'hybrid', 'Fire incidents, rescue operations, and fire safety data'),
('emergency_response', 'Emergency Response System', 'hybrid', 'Emergency calls, response times, and emergency services data'),
('community_policing', 'Community Policing & Surveillance', 'hybrid', 'Community reports, surveillance data, and community engagement'),
('target_audience', 'Target Audience Segmentation', 'database', 'Audience demographics, risk profiles, and segmentation data')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- ============================================
-- 8. CREATE VIEWS FOR EASY QUERYING
-- ============================================
-- View for active integrations
CREATE OR REPLACE VIEW v_active_integrations AS
SELECT 
    es.id as system_id,
    es.system_name,
    es.display_name,
    es.system_type,
    esc.connection_type,
    esc.connection_status,
    esc.last_connection_test,
    COUNT(DISTINCT edm.id) as active_mappings_count,
    COUNT(DISTINCT msm.id) as module_access_count
FROM `campaign_department_external_systems` es
LEFT JOIN `campaign_department_external_system_connections` esc ON esc.system_id = es.id AND esc.is_active = TRUE
LEFT JOIN `campaign_department_external_data_mappings` edm ON edm.system_id = es.id AND edm.is_active = TRUE
LEFT JOIN `campaign_department_module_system_mappings` msm ON msm.system_id = es.id AND msm.is_active = TRUE
WHERE es.is_active = TRUE
GROUP BY es.id, es.system_name, es.display_name, es.system_type, esc.connection_type, esc.connection_status, esc.last_connection_test;

-- View for module integration capabilities
CREATE OR REPLACE VIEW v_module_integrations AS
SELECT 
    msm.module_name,
    es.system_name,
    es.display_name,
    msm.access_type,
    COUNT(DISTINCT edm.id) as available_mappings,
    esc.connection_status
FROM `campaign_department_module_system_mappings` msm
INNER JOIN `campaign_department_external_systems` es ON es.id = msm.system_id
LEFT JOIN `campaign_department_external_data_mappings` edm ON edm.system_id = es.id AND edm.is_active = TRUE
LEFT JOIN `campaign_department_external_system_connections` esc ON esc.system_id = es.id AND esc.is_active = TRUE
WHERE msm.is_active = TRUE AND es.is_active = TRUE
GROUP BY msm.module_name, es.system_name, es.display_name, msm.access_type, esc.connection_status;

