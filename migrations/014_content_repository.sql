-- Content Repository Module - Enhanced content_items table
-- Centralized library for storing, managing, approving, and distributing educational materials
-- Supports campaign planning, audience-targeted advisories, and pre-calamity seminars

-- Enhance existing content_items table with Content Repository fields
ALTER TABLE content_items
ADD COLUMN IF NOT EXISTS file_reference VARCHAR(500) NULL AFTER file_path,
ADD COLUMN IF NOT EXISTS version_number INT UNSIGNED NOT NULL DEFAULT 1 AFTER approval_status,
ADD COLUMN IF NOT EXISTS approved_by INT UNSIGNED NULL AFTER version_number,
ADD COLUMN IF NOT EXISTS approval_notes TEXT NULL AFTER approved_by,
ADD COLUMN IF NOT EXISTS date_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER approval_notes;

-- Add foreign key for approved_by if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'content_items';
SET @constraintname = 'fk_content_approver';
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE CONSTRAINT_SCHEMA = @dbname 
     AND TABLE_NAME = @tablename 
     AND CONSTRAINT_NAME = @constraintname) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD CONSTRAINT ', @constraintname, ' FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL')
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update approval_status enum to include 'draft' (if not already present)
-- Note: This may fail if enum already has 'draft', which is fine
ALTER TABLE content_items 
MODIFY COLUMN approval_status ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'draft';

-- Update content_type enum to match Content Repository requirements
ALTER TABLE content_items
MODIFY COLUMN content_type ENUM('text', 'image', 'video', 'link', 'file', 'poster', 'guideline', 'infographic') NOT NULL DEFAULT 'text';

-- Make campaign_id nullable (content can exist without campaign)
ALTER TABLE content_items
MODIFY COLUMN campaign_id INT UNSIGNED NULL;

-- Rename intended_audience to intended_audience_segment for consistency
ALTER TABLE content_items
CHANGE COLUMN intended_audience intended_audience_segment VARCHAR(255) NULL;

-- Version history table for tracking content changes (uses content_items.id as content_id)
CREATE TABLE IF NOT EXISTS content_item_versions (
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
    CONSTRAINT fk_content_item_versions_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_item_versions_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_content_item_version (content_id, version_number),
    INDEX idx_content_item_versions_content (content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Many-to-many relationship between campaigns and content items (for Content Repository)
CREATE TABLE IF NOT EXISTS campaign_content_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    attached_by INT UNSIGNED NULL,
    attached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_content_items_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_content_items_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_content_items_user FOREIGN KEY (attached_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_campaign_content_item (campaign_id, content_id),
    INDEX idx_campaign_content_items_campaign (campaign_id),
    INDEX idx_campaign_content_items_content (content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better search performance
CREATE INDEX IF NOT EXISTS idx_content_items_type ON content_items(content_type);
CREATE INDEX IF NOT EXISTS idx_content_items_hazard ON content_items(hazard_category);
CREATE INDEX IF NOT EXISTS idx_content_items_status ON content_items(approval_status);
CREATE INDEX IF NOT EXISTS idx_content_items_audience ON content_items(intended_audience_segment(100));
