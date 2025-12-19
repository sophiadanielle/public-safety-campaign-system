-- Add status to campaign_schedules
USE LGU;

ALTER TABLE campaign_schedules
    ADD COLUMN status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending' AFTER notes;





