-- Check which columns exist in the tables
USE `LGU`;

-- Check agency coordination table columns
SELECT 'Agency Coordination Table Columns:' AS info;
SHOW COLUMNS FROM `campaign_department_event_agency_coordination`;

-- Check events table columns  
SELECT 'Events Table Columns (checking for post_event_notes):' AS info;
SHOW COLUMNS FROM `campaign_department_events` WHERE Field = 'post_event_notes';

-- If post_event_notes doesn't show up above, it needs to be added
