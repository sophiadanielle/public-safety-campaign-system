-- Add survey status for publish control
USE LGU;

ALTER TABLE surveys
    ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft' AFTER description;


