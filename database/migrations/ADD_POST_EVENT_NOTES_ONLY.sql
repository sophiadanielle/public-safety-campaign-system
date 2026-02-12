-- Add only the post_event_notes column (action_type already exists)
USE `LGU`;

ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

-- Verify it was added
SELECT 'Column added successfully!' AS status;
SHOW COLUMNS FROM `campaign_department_events` LIKE 'post_event_notes';
