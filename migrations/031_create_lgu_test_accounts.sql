-- Create LGU Governance Test Accounts
-- Password for all accounts: "pass123"
-- Run this AFTER migrations/029_lgu_governance_roles.sql
-- DO NOT modify the existing admin account (admin@barangay1.qc.gov.ph)

SET NAMES utf8mb4;

-- Password hash for "pass123" (generated with PHP password_hash)
SET @password_hash = '$2y$10$XOpu22qLUBKFYAHEojQP.eREkqh5uzHQ6Bu.13qmcf9X8AUvNIxyC';

-- Ensure Barangay 1 exists
INSERT IGNORE INTO `campaign_department_barangays` (id, name, city, province, region) VALUES
(1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR');

-- ============================================
-- CREATE TEST ACCOUNTS FOR EACH LGU ROLE
-- ============================================

-- Staff Account (Entry-level, can create drafts only)
INSERT IGNORE INTO `campaign_department_users` (role_id, barangay_id, name, email, password_hash, is_active)
SELECT r.id, 1, 'Test Staff', 'staff@barangay1.qc.gov.ph', @password_hash, 1
FROM `campaign_department_roles` r 
WHERE r.name = 'staff'
LIMIT 1;

-- Secretary Account (Can mark drafts as Pending Review)
INSERT IGNORE INTO `campaign_department_users` (role_id, barangay_id, name, email, password_hash, is_active)
SELECT r.id, 1, 'Test Secretary', 'secretary@barangay1.qc.gov.ph', @password_hash, 1
FROM `campaign_department_roles` r 
WHERE r.name = 'secretary'
LIMIT 1;

-- Kagawad Account (Can recommend for approval)
INSERT IGNORE INTO `campaign_department_users` (role_id, barangay_id, name, email, password_hash, is_active)
SELECT r.id, 1, 'Test Kagawad', 'kagawad@barangay1.qc.gov.ph', @password_hash, 1
FROM `campaign_department_roles` r 
WHERE r.name = 'kagawad'
LIMIT 1;

-- Captain Account (Final authority, can approve/reject)
INSERT IGNORE INTO `campaign_department_users` (role_id, barangay_id, name, email, password_hash, is_active)
SELECT r.id, 1, 'Test Captain', 'captain@barangay1.qc.gov.ph', @password_hash, 1
FROM `campaign_department_roles` r 
WHERE r.name = 'captain'
LIMIT 1;

-- Partner Account (External partner, limited access)
INSERT IGNORE INTO `campaign_department_users` (role_id, barangay_id, name, email, password_hash, is_active)
SELECT r.id, 1, 'Test Partner', 'partner@barangay1.qc.gov.ph', @password_hash, 1
FROM `campaign_department_roles` r 
WHERE r.name = 'partner'
LIMIT 1;

-- Viewer Account (Read-only access)
INSERT IGNORE INTO `campaign_department_users` (role_id, barangay_id, name, email, password_hash, is_active)
SELECT r.id, 1, 'Test Viewer', 'viewer@barangay1.qc.gov.ph', @password_hash, 1
FROM `campaign_department_roles` r 
WHERE r.name = 'viewer'
LIMIT 1;

-- ============================================
-- VERIFY ACCOUNTS WERE CREATED
-- ============================================

SELECT 
    u.id,
    u.name,
    u.email,
    r.name as role_name,
    u.is_active,
    'Password: pass123' as credentials
FROM `campaign_department_users` u
JOIN `campaign_department_roles` r ON r.id = u.role_id
WHERE u.email IN (
    'staff@barangay1.qc.gov.ph',
    'secretary@barangay1.qc.gov.ph',
    'kagawad@barangay1.qc.gov.ph',
    'captain@barangay1.qc.gov.ph',
    'partner@barangay1.qc.gov.ph',
    'viewer@barangay1.qc.gov.ph'
)
ORDER BY 
    CASE r.name
        WHEN 'staff' THEN 1
        WHEN 'secretary' THEN 2
        WHEN 'kagawad' THEN 3
        WHEN 'captain' THEN 4
        WHEN 'partner' THEN 5
        WHEN 'viewer' THEN 6
        ELSE 7
    END;



