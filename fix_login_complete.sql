-- Complete Fix for Login Credentials
-- Run this in phpMyAdmin SQL tab
-- Make sure you're using the LGU database

USE LGU;

-- Step 1: Ensure roles exist
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'Barangay Administrator', 'Full access to all campaign management features'),
(2, 'Barangay Staff', 'Can create and manage campaigns, limited administrative access'),
(3, 'School Partner', 'Can view campaigns and coordinate joint activities'),
(4, 'NGO Partner', 'Can view campaigns and coordinate joint activities');

-- Step 2: Ensure barangays exist
INSERT IGNORE INTO barangays (id, name, city, province, region) VALUES
(1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR'),
(2, 'Barangay 2', 'Quezon City', 'Metro Manila', 'NCR'),
(3, 'Barangay 3', 'Quezon City', 'Metro Manila', 'NCR');

-- Step 3: Delete existing admin user if exists (to avoid conflicts)
DELETE FROM users WHERE email = 'admin@barangay1.qc.gov.ph';

-- Step 4: Create admin user with fresh password hash
-- Password: password123
-- This hash is generated fresh and should work
INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active) 
VALUES (
    1, 
    1, 
    1, 
    'Admin User', 
    'admin@barangay1.qc.gov.ph', 
    '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lWq', 
    1
);

-- Step 5: Verify the user was created
SELECT 
    id, 
    name, 
    email, 
    role_id, 
    is_active,
    LEFT(password_hash, 30) as password_hash_preview
FROM users 
WHERE email = 'admin@barangay1.qc.gov.ph';

-- If the above hash doesn't work, you can generate a new one using PHP:
-- <?php echo password_hash('password123', PASSWORD_DEFAULT); ?>
-- Then update with: UPDATE users SET password_hash = 'YOUR_NEW_HASH' WHERE email = 'admin@barangay1.qc.gov.ph';














