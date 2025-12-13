-- Sample seed data for quick testing
USE campaign_db;

-- Barangay
INSERT INTO barangays (name, city, province, region) VALUES
('Barangay 1', 'Sample City', 'Sample Province', 'Region I')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Roles
INSERT INTO roles (name, description) VALUES
('campaign_creator', 'User who can create and manage campaigns')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Users (password: "password")
INSERT INTO users (role_id, barangay_id, name, email, password_hash, is_active)
VALUES (
    (SELECT id FROM roles WHERE name = 'campaign_creator' LIMIT 1),
    (SELECT id FROM barangays WHERE name = 'Barangay 1' LIMIT 1),
    'Test User',
    'test.user@example.com',
    '$2y$10$CwTycUXWue0Thq9StjUM0uJ8K8vLwKsFcfoTmyaKOA1NV1npZV8aS',
    1
)
ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), is_active = VALUES(is_active);


