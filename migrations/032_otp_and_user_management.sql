-- Migration: OTP and User Management
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

-- Add user_type column to campaign_department_users if not exists
-- user_type: Super Admin, Admin, Staff, Employee
ALTER TABLE `campaign_department_users` 
ADD COLUMN IF NOT EXISTS `user_type` ENUM('Super Admin', 'Admin', 'Staff', 'Employee') DEFAULT 'Employee' AFTER `name`;

-- Add avatar_url column for profile pictures
ALTER TABLE `campaign_department_users` 
ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(500) NULL AFTER `user_type`;

-- Add archived column for soft delete functionality
ALTER TABLE `campaign_department_users` 
ADD COLUMN IF NOT EXISTS `archived` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- Add archived_at timestamp
ALTER TABLE `campaign_department_users` 
ADD COLUMN IF NOT EXISTS `archived_at` DATETIME NULL AFTER `archived`;

-- Add phone_number column if not exists
ALTER TABLE `campaign_department_users` 
ADD COLUMN IF NOT EXISTS `phone_number` VARCHAR(20) NULL AFTER `avatar_url`;

-- Create index for archived users
CREATE INDEX IF NOT EXISTS idx_users_archived ON `campaign_department_users` (archived);

-- Clean up expired OTPs (can be run periodically)
-- DELETE FROM `campaign_department_otp` WHERE expires_at < NOW() OR is_used = 1;
