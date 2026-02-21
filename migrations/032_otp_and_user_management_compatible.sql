-- Migration: OTP and User Management (MySQL 5.7+ Compatible)
-- Adds OTP table and extends campaign_department_users for user management

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create OTP table for login verification
CREATE TABLE IF NOT EXISTS `campaign_department_otp` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(150) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES `campaign_department_users`(id) ON DELETE CASCADE,
    INDEX idx_otp_email (email),
    INDEX idx_otp_code (otp_code),
    INDEX idx_otp_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add user_type column to campaign_department_users
-- Check if column exists before adding
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'campaign_department_users' 
AND COLUMN_NAME = 'user_type';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `campaign_department_users` ADD COLUMN `user_type` ENUM(''Super Admin'', ''Admin'', ''Staff'', ''Employee'') DEFAULT ''Employee'' AFTER `name`',
    'SELECT ''Column user_type already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add avatar_url column for profile pictures
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'campaign_department_users' 
AND COLUMN_NAME = 'avatar_url';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `campaign_department_users` ADD COLUMN `avatar_url` VARCHAR(500) NULL AFTER `user_type`',
    'SELECT ''Column avatar_url already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add archived column for soft delete functionality
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'campaign_department_users' 
AND COLUMN_NAME = 'archived';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `campaign_department_users` ADD COLUMN `archived` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`',
    'SELECT ''Column archived already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add archived_at timestamp
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'campaign_department_users' 
AND COLUMN_NAME = 'archived_at';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `campaign_department_users` ADD COLUMN `archived_at` DATETIME NULL AFTER `archived`',
    'SELECT ''Column archived_at already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add phone_number column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'campaign_department_users' 
AND COLUMN_NAME = 'phone_number';

SET @query = IF(@col_exists = 0, 
    'ALTER TABLE `campaign_department_users` ADD COLUMN `phone_number` VARCHAR(20) NULL AFTER `avatar_url`',
    'SELECT ''Column phone_number already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create index for archived users
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'campaign_department_users' 
AND INDEX_NAME = 'idx_users_archived';

SET @query = IF(@index_exists = 0, 
    'CREATE INDEX idx_users_archived ON `campaign_department_users` (archived)',
    'SELECT ''Index idx_users_archived already exists'' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Clean up expired OTPs (can be run periodically)
-- DELETE FROM `campaign_department_otp` WHERE expires_at < NOW() OR is_used = 1;
