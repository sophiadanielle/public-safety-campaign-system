-- Create Viewer Role for Registration
-- Run this SQL script to ensure the 'viewer' role exists in the database
-- This allows users to sign up with role "viewer"

USE LGU;

-- Create 'viewer' role if it doesn't exist
INSERT IGNORE INTO `campaign_department_roles` (name, description) 
VALUES ('viewer', 'Viewer (Partner Representative) - Read-only access to campaigns, events, surveys, and impact reports');

-- Verify the role was created
SELECT id, name, description 
FROM `campaign_department_roles` 
WHERE LOWER(name) = 'viewer';

-- Note: If you want to use existing partner roles instead:
-- Role ID 3 = 'School Partner' → maps to 'viewer' via get_user_role.php
-- Role ID 4 = 'NGO Partner' → maps to 'viewer' via get_user_role.php
-- These will also work, but users should sign up with "partner" instead of "viewer"

