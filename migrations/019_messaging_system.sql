-- Messaging System
-- Migration 019: User-to-user messaging for collaboration

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create conversations table (threads between users)
CREATE TABLE IF NOT EXISTS conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participant1_id INT UNSIGNED NOT NULL,
    participant2_id INT UNSIGNED NOT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_participant1 (participant1_id),
    INDEX idx_participant2 (participant2_id),
    INDEX idx_last_message (last_message_at),
    UNIQUE KEY unique_conversation (participant1_id, participant2_id),
    CONSTRAINT fk_conv_participant1 FOREIGN KEY (participant1_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_participant2 FOREIGN KEY (participant2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Create messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    message_text TEXT NOT NULL,
    context_type ENUM('campaign', 'event', 'content', 'general') NOT NULL DEFAULT 'general',
    context_id INT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_context (context_type, context_id),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;



