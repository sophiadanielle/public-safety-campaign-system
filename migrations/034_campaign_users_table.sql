-- Create campaign_users table for login authentication
-- Migration: 034_campaign_users_table.sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `campaign_users` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(150) NOT NULL,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    avatar_url VARCHAR(500) NULL,
    user_type VARCHAR(50) NOT NULL DEFAULT 'user',
    archived TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_archived (archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
