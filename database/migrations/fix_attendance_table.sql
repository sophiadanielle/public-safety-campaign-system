-- Fix attendance table - add default value for check_in column
USE `LGU`;

-- Add default value to check_in column if it exists
ALTER TABLE `campaign_department_attendance` 
MODIFY COLUMN `check_in` TINYINT(1) DEFAULT 1;

-- Also ensure checkin_timestamp has proper default
ALTER TABLE `campaign_department_attendance` 
MODIFY COLUMN `checkin_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

SELECT 'Attendance table fixed!' AS status;
