-- =====================================================
-- Fix Agency Coordination and Events Errors
-- Purpose: Add missing columns causing errors
-- Date: February 12, 2026
-- Issues Fixed:
--   1. action_type column missing in agency_coordination
--   2. post_event_notes column missing in events table
-- =====================================================

USE `LGU`;

-- =====================================================
-- 1. ADD action_type TO campaign_department_event_agency_coordination
-- =====================================================

-- Check if column exists first, then add if missing
SET @dbname = DATABASE();
SET @tablename = 'campaign_department_event_agency_coordination';
SET @columnname = 'action_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column action_type already exists' AS message;",
  "ALTER TABLE campaign_department_event_agency_coordination ADD COLUMN action_type VARCHAR(100) NULL AFTER request_details;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- 2. ADD post_event_notes TO campaign_department_events
-- =====================================================

SET @tablename = 'campaign_department_events';
SET @columnname = 'post_event_notes';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column post_event_notes already exists' AS message;",
  "ALTER TABLE campaign_department_events ADD COLUMN post_event_notes TEXT NULL;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- VERIFICATION
-- =====================================================

SELECT 'Migration completed!' AS status;

-- Show the columns in agency_coordination table
SELECT 'Agency Coordination Columns:' AS info;
SHOW COLUMNS FROM campaign_department_event_agency_coordination;

-- Show the columns in events table (just the new one)
SELECT 'Events Table - post_event_notes column:' AS info;
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'LGU' 
  AND TABLE_NAME = 'campaign_department_events' 
  AND COLUMN_NAME = 'post_event_notes';
