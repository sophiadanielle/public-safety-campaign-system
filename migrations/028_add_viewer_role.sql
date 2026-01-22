-- Add viewer role for RBAC system
-- This role has read-only access (viewer permissions)

SET NAMES utf8mb4;

-- Create viewer role if it doesn't exist
INSERT IGNORE INTO `campaign_department_roles` (name, description) VALUES
('viewer', 'Viewer - Read-only access to campaigns and content');

-- Get viewer role ID and assign minimal read permissions
-- Viewer can only view (not create/edit/delete)
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'viewer' 
AND p.name IN ('reports.view');

-- Note: If more granular permissions exist, viewer should only get:
-- - campaigns.view (if exists)
-- - content.view (if exists)  
-- - reports.view
-- But NOT: create, update, delete, approve, manage permissions



