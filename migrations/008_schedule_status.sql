-- Add status to campaign_schedules
USE campaign_db;

ALTER TABLE campaign_schedules
    ADD COLUMN status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending' AFTER notes;





