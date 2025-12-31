-- Notifications System
-- Migration 018: User notifications table for dashboard alerts

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create notifications table
CREATE TABLE IF NOT EXISTS `campaign_department_notifications` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    type ENUM('campaign', 'event', 'content', 'system', 'alert', 'reminder') NOT NULL DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(500) NULL,
    icon VARCHAR(50) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES `campaign_department_users`(id) ON DELETE CASCADE
) ENGINE=InnoDB;



