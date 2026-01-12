-- Extend content schema to support visibility, tagging, and usage linkage
USE LGU;

-- Visibility on content items
ALTER TABLE `campaign_department_content_items`
    ADD COLUMN visibility ENUM('public','private','internal') NOT NULL DEFAULT 'public' AFTER content_type;

-- Content tags junction
CREATE TABLE IF NOT EXISTS `campaign_department_content_tags` (
    content_item_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (content_item_id, tag_id),
    CONSTRAINT fk_content_tags_content FOREIGN KEY (content_item_id) REFERENCES `campaign_department_content_items`(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_tags_tag FOREIGN KEY (tag_id) REFERENCES `campaign_department_tags`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link content usage to campaign/event for traceability
ALTER TABLE `campaign_department_content_usage`
    ADD COLUMN campaign_id INT UNSIGNED NULL AFTER tag_id,
    ADD COLUMN event_id INT UNSIGNED NULL AFTER campaign_id,
    ADD CONSTRAINT fk_content_usage_campaign FOREIGN KEY (campaign_id) REFERENCES `campaign_department_campaigns`(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_content_usage_event FOREIGN KEY (event_id) REFERENCES `campaign_department_events`(id) ON DELETE SET NULL;


