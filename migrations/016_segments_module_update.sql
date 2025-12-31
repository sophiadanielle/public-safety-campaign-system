-- Module 3: Target Audience Segmentation - Schema Update
-- Updates audience_segments table to match exact requirements
-- Scope: Barangays within Quezon City only

USE LGU;

-- Rename name to segment_name (if column exists)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'campaign_department_audience_segments' 
    AND COLUMN_NAME = 'name');
    
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `campaign_department_audience_segments` CHANGE COLUMN name segment_name VARCHAR(150) NOT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update geographic_scope as ENUM (Barangay, Zone, Purok)
ALTER TABLE `campaign_department_audience_segments`
    MODIFY COLUMN geographic_scope ENUM('Barangay', 'Zone', 'Purok') NULL;

-- Add location_reference (Barangay name - QC only) if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'campaign_department_audience_segments' 
    AND COLUMN_NAME = 'location_reference');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN location_reference VARCHAR(255) NULL AFTER geographic_scope',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update sector_type to exact ENUM values
ALTER TABLE `campaign_department_audience_segments`
    MODIFY COLUMN sector_type ENUM('Households', 'Youth', 'Senior Citizens', 'Schools', 'NGOs') NULL;

-- Update risk_level to exact ENUM values (capitalize)
ALTER TABLE `campaign_department_audience_segments`
    MODIFY COLUMN risk_level ENUM('Low', 'Medium', 'High') NULL;

-- Rename segmentation_basis to basis_of_segmentation with exact ENUM values (if column exists)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'campaign_department_audience_segments' 
    AND COLUMN_NAME = 'segmentation_basis');
    
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `campaign_department_audience_segments` CHANGE COLUMN segmentation_basis basis_of_segmentation ENUM(\'Historical trend\', \'Inspection results\', \'Attendance records\', \'Incident pattern reference\') NULL',
    'ALTER TABLE `campaign_department_audience_segments` ADD COLUMN basis_of_segmentation ENUM(\'Historical trend\', \'Inspection results\', \'Attendance records\', \'Incident pattern reference\') NULL AFTER risk_level');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update audience_members table to support segment members view
ALTER TABLE `campaign_department_audience_members`
    ADD COLUMN IF NOT EXISTS sector VARCHAR(100) NULL AFTER full_name,
    ADD COLUMN IF NOT EXISTS barangay VARCHAR(255) NULL AFTER sector,
    ADD COLUMN IF NOT EXISTS zone VARCHAR(255) NULL AFTER barangay,
    ADD COLUMN IF NOT EXISTS purok VARCHAR(255) NULL AFTER zone;

-- Create participation_history view (read-only) for historical participation data
CREATE OR REPLACE VIEW participation_history AS
SELECT 
    c.id AS campaign_id,
    c.title AS campaign_name,
    e.id AS event_id,
    e.name AS event_name,
    e.event_type,
    e.event_date,
    e.attendance_count,
    a.check_in,
    a.check_out,
    am.id AS member_id,
    am.full_name AS member_name,
    s.id AS segment_id,
    s.segment_name
FROM `campaign_department_campaigns` c
LEFT JOIN `campaign_department_events` e ON e.campaign_id = c.id
LEFT JOIN `campaign_department_attendance` a ON a.event_id = e.id
LEFT JOIN `campaign_department_audience_members` am ON am.id = a.audience_member_id
LEFT JOIN `campaign_department_audience_segments` s ON s.id = am.segment_id
ORDER BY e.event_date DESC, a.check_in DESC;

-- Ensure campaign_audience linking table exists (for segment-to-campaign linking)
CREATE TABLE IF NOT EXISTS `campaign_department_campaign_audience` (
    campaign_id INT UNSIGNED NOT NULL,
    segment_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (campaign_id, segment_id),
    CONSTRAINT fk_campaign_audience_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_audience_segment FOREIGN KEY (segment_id) REFERENCES `campaign_department_audience_segments`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
