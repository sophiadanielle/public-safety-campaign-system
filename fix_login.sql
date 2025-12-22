-- Quick Fix for Login Credentials
-- Run this SQL script in phpMyAdmin or MySQL command line

USE LGU;

-- Make sure roles exist first
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'Barangay Administrator', 'Full access to all campaign management features'),
(2, 'Barangay Staff', 'Can create and manage campaigns, limited administrative access'),
(3, 'School Partner', 'Can view campaigns and coordinate joint activities'),
(4, 'NGO Partner', 'Can view campaigns and coordinate joint activities');

-- Make sure barangays exist
INSERT IGNORE INTO barangays (id, name, city, province, region) VALUES
(1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR'),
(2, 'Barangay 2', 'Quezon City', 'Metro Manila', 'NCR'),
(3, 'Barangay 3', 'Quezon City', 'Metro Manila', 'NCR');

-- Create/Update Admin User
-- Password: password123
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active) 
VALUES (1, 1, 1, 'Admin User', 'admin@barangay1.qc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE 
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    role_id = 1,
    is_active = 1;

-- Create/Update Staff User
INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active) 
VALUES (2, 2, 1, 'Staff Member', 'staff@barangay1.qc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE 
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    role_id = 2,
    is_active = 1;

-- Verify the user was created/updated
SELECT id, name, email, role_id, is_active FROM users WHERE email = 'admin@barangay1.qc.gov.ph';











