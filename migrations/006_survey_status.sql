-- Add survey status for publish control
USE LGU;

ALTER TABLE `campaign_department_surveys`
    ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft' AFTER description;


