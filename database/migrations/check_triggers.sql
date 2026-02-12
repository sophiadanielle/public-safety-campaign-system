-- Check for triggers on agency_coordination table
USE `LGU`;

-- Show all triggers on the agency coordination table
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    ACTION_STATEMENT,
    ACTION_TIMING
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE EVENT_OBJECT_SCHEMA = 'LGU'
  AND EVENT_OBJECT_TABLE = 'campaign_department_event_agency_coordination';

-- Show table structure
SHOW CREATE TABLE `campaign_department_event_agency_coordination`;
