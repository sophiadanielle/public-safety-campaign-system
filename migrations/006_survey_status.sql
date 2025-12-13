-- Add survey status for publish control
USE campaign_db;

ALTER TABLE surveys
    ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft' AFTER description;


