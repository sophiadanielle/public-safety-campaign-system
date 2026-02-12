-- Add participant_identifier column to attendance table
USE `LGU`;

ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `participant_identifier` VARCHAR(255) NULL AFTER `event_id`;

ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `checkin_method` ENUM('QR', 'manual', 'online') DEFAULT 'manual' AFTER `participant_identifier`;

ALTER TABLE `campaign_department_attendance` 
ADD COLUMN `checkin_notes` TEXT NULL AFTER `checkin_method`;

ALTER TABLE `campaign_department_attendance` 
ADD INDEX `idx_participant_identifier` (`participant_identifier`);
