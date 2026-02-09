-- Migration 033: Add linked_campaign_id column to events table
-- This column is used to link events to campaigns for impact tracking

ALTER TABLE `campaign_department_events` 
ADD COLUMN `linked_campaign_id` INT(11) NULL AFTER `event_id`,
ADD KEY `idx_linked_campaign_id` (`linked_campaign_id`),
ADD CONSTRAINT `fk_events_linked_campaign` 
    FOREIGN KEY (`linked_campaign_id`) 
    REFERENCES `campaign_department_campaigns` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;
