-- LGU Governance Roles for Public Safety Campaign Management System
-- Implements: staff → secretary → kagawad → barangay captain approval chain
-- Run this migration to add required LGU governance roles

SET NAMES utf8mb4;

-- Create LGU governance roles if they don't exist
INSERT IGNORE INTO `campaign_department_roles` (name, description) VALUES
('admin', 'Technical Administrator - Full system access, user management, emergency overrides'),
('staff', 'Barangay Staff - Can create campaign drafts, cannot approve'),
('secretary', 'Barangay Secretary - Can review staff drafts, mark as Pending Review, cannot finalize'),
('kagawad', 'Kagawad (Public Safety Committee) - Can review pending campaigns, recommend approval, cannot finalize'),
('captain', 'Barangay Captain - Final authority, can approve or reject campaigns'),
('partner', 'External Partner - Limited access, can view assigned campaigns, upload collaboration data'),
('viewer', 'Viewer - Read-only access for internal use');

-- Note: Existing roles ('Barangay Administrator', 'Barangay Staff', etc.) remain for backward compatibility
-- New LGU workflow uses: staff → secretary → kagawad → captain

-- Assign permissions based on LGU governance structure

-- Admin: All permissions (technical admin)
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'admin';

-- Staff: Can create campaigns (drafts only)
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'staff' 
AND p.name IN ('campaigns.create', 'campaigns.update', 'reports.view');

-- Secretary: Can review and mark as pending
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'secretary' 
AND p.name IN ('campaigns.create', 'campaigns.update', 'reports.view');

-- Kagawad: Can review and recommend (but not finalize)
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'kagawad' 
AND p.name IN ('campaigns.create', 'campaigns.update', 'reports.view');

-- Captain: Can approve/reject (final authority)
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'captain' 
AND p.name IN ('campaigns.create', 'campaigns.update', 'campaigns.approve', 'campaigns.delete', 'reports.view');

-- Partner: Limited view and collaboration
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'partner' 
AND p.name IN ('reports.view');

-- Viewer: Read-only
INSERT IGNORE INTO `campaign_department_role_permissions` (role_id, permission_id)
SELECT r.id, p.id 
FROM `campaign_department_roles` r
CROSS JOIN `campaign_department_permissions` p
WHERE r.name = 'viewer' 
AND p.name IN ('reports.view');


