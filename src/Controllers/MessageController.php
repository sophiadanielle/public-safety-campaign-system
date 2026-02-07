<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use PDO;

class MessageController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
    }

    /**
     * Get conversations list (for messages dropdown)
     */
    public function getConversations(?array $user, array $params = []): array
    {
        try {
            if (!$user) {
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

            $userId = (int) $user['id'];
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

            // Check if tables exist first
            try {
                $checkConv = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_conversations'");
                $checkUsers = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_users'");
                if ($checkConv->rowCount() === 0 || $checkUsers->rowCount() === 0) {
                    error_log('MessageController::getConversations - Required tables do not exist');
                    return [
                        'data' => [],
                        'unread_count' => 0,
                    ];
                }
            } catch (\PDOException $e) {
                error_log('MessageController::getConversations - Error checking table existence: ' . $e->getMessage());
                return [
                    'data' => [],
                    'unread_count' => 0,
                ];
            }

            // Get conversations where user is participant1 or participant2
            // First, get all conversations for the user
            $sql = "
                SELECT 
                    c.id as conversation_id,
                    CASE 
                        WHEN c.participant1_id = :user_id THEN c.participant2_id
                        ELSE c.participant1_id
                    END as other_user_id,
                    u.name as other_user_name,
                    u.email as other_user_email,
                    COALESCE(c.last_message_at, c.created_at) as last_message_at
                FROM campaign_department_conversations c
                INNER JOIN campaign_department_users u ON (
                    (c.participant1_id = :user_id AND u.id = c.participant2_id) OR
                    (c.participant2_id = :user_id AND u.id = c.participant1_id)
                )
                WHERE c.participant1_id = :user_id OR c.participant2_id = :user_id
                ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
                LIMIT :limit
            ";

            try {
                $stmt = $this->pdo->prepare($sql);
                if ($stmt === false) {
                    throw new \RuntimeException('Failed to prepare SQL statement');
                }
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                error_log('MessageController::getConversations PDO error: ' . $e->getMessage());
                error_log('MessageController::getConversations SQL: ' . $sql);
                // Return empty data instead of crashing
                $conversations = [];
            }

            // Check if messages table exists before enriching
            $messagesTableExists = true;
            try {
                $checkMsg = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_messages'");
                $messagesTableExists = $checkMsg->rowCount() > 0;
            } catch (\PDOException $e) {
                $messagesTableExists = false;
                error_log('MessageController::getConversations - Error checking messages table: ' . $e->getMessage());
            }

            // Check if messages table exists before enriching
            $messagesTableExists = true;
            try {
                $checkMsg = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_messages'");
                $messagesTableExists = $checkMsg->rowCount() > 0;
            } catch (\PDOException $e) {
                $messagesTableExists = false;
                error_log('MessageController::getConversations - Error checking messages table: ' . $e->getMessage());
            }

            // Now enrich each conversation with last message and unread count
            foreach ($conversations as &$conv) {
                $convId = (int) $conv['conversation_id'];
                
                if (!$messagesTableExists) {
                    // Set defaults if messages table doesn't exist
                    $conv['last_message'] = null;
                    $conv['last_message_read'] = true;
                    $conv['unread_count'] = 0;
                    continue;
                }
                
                try {
                    // Get last message
                    $lastMsgStmt = $this->pdo->prepare('
                        SELECT message_text, created_at, is_read
                        FROM campaign_department_messages
                        WHERE conversation_id = :conv_id
                        ORDER BY created_at DESC
                        LIMIT 1
                    ');
                    if ($lastMsgStmt !== false) {
                        $lastMsgStmt->execute(['conv_id' => $convId]);
                        $lastMsg = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);
                        
                        $conv['last_message'] = $lastMsg ? $lastMsg['message_text'] : null;
                        $conv['last_message_at'] = $lastMsg ? $lastMsg['created_at'] : $conv['last_message_at'];
                        $conv['last_message_read'] = $lastMsg ? (bool) $lastMsg['is_read'] : true;
                    } else {
                        $conv['last_message'] = null;
                        $conv['last_message_read'] = true;
                    }
                    
                    // Get unread count for this conversation
                    $unreadStmt = $this->pdo->prepare('
                        SELECT COUNT(*) 
                        FROM campaign_department_messages
                        WHERE conversation_id = :conv_id AND recipient_id = :user_id AND is_read = 0
                    ');
                    if ($unreadStmt !== false) {
                        $unreadStmt->execute(['conv_id' => $convId, 'user_id' => $userId]);
                        $conv['unread_count'] = (int) $unreadStmt->fetchColumn();
                    } else {
                        $conv['unread_count'] = 0;
                    }
                } catch (\Throwable $e) {
                    error_log('MessageController::getConversations enrichment error: ' . $e->getMessage());
                    // Set defaults if query fails
                    $conv['last_message'] = null;
                    $conv['last_message_read'] = true;
                    $conv['unread_count'] = 0;
                }
            }
            unset($conv);

            // Get total unread count (only if messages table exists)
            $totalUnread = 0;
            if ($messagesTableExists) {
                try {
                    $unreadStmt = $this->pdo->prepare('
                        SELECT COUNT(*) FROM campaign_department_messages 
                        WHERE recipient_id = :user_id AND is_read = 0
                    ');
                    if ($unreadStmt !== false) {
                        $unreadStmt->execute(['user_id' => $userId]);
                        $totalUnread = (int) $unreadStmt->fetchColumn();
                    }
                } catch (\PDOException $e) {
                    error_log('MessageController::getConversations unread count error: ' . $e->getMessage());
                    // Continue with totalUnread = 0
                }
            }

            return [
                'data' => $conversations ?: [],
                'unread_count' => $totalUnread,
            ];
        } catch (\Throwable $e) {
            error_log('MessageController::getConversations error: ' . $e->getMessage());
            error_log('MessageController::getConversations stack: ' . $e->getTraceAsString());
            // Don't set 500 status - return valid JSON with empty data
            return ['data' => [], 'unread_count' => 0];
        }
    }

    /**
     * Get messages in a conversation
     */
    public function getMessages(?array $user, array $params = []): array
    {
            if (!$user) {
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

        $userId = (int) $user['id'];
        $conversationId = (int) ($params['id'] ?? 0);
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;

        // Verify user is part of this conversation
        $checkStmt = $this->pdo->prepare('
            SELECT id FROM campaign_department_conversations 
            WHERE id = :conv_id AND (participant1_id = :user_id OR participant2_id = :user_id)
        ');
        $checkStmt->execute(['conv_id' => $conversationId, 'user_id' => $userId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Conversation not found'];
        }

        // Get messages
        $stmt = $this->pdo->prepare('
            SELECT 
                m.id,
                m.sender_id,
                m.recipient_id,
                m.message_text,
                m.context_type,
                m.context_id,
                m.is_read,
                m.created_at,
                u.name as sender_name,
                u.email as sender_email
            FROM campaign_department_messages m
            INNER JOIN campaign_department_users u ON u.id = m.sender_id
            WHERE m.conversation_id = :conv_id
            ORDER BY m.created_at ASC
            LIMIT :limit
        ');
        $stmt->bindValue(':conv_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages as read
        $this->pdo->prepare('
            UPDATE campaign_department_messages 
            SET is_read = 1, read_at = NOW() 
            WHERE conversation_id = :conv_id AND recipient_id = :user_id AND is_read = 0
        ')->execute(['conv_id' => $conversationId, 'user_id' => $userId]);

        return ['data' => $messages];
    }

    /**
     * Send a message
     */
    public function sendMessage(?array $user, array $params = []): array
    {
            if (!$user) {
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $recipientId = (int) ($input['recipient_id'] ?? 0);
        $messageText = trim($input['message'] ?? '');
        $contextType = $input['context_type'] ?? 'general';
        $contextId = isset($input['context_id']) ? (int) $input['context_id'] : null;

        if (!$recipientId || !$messageText) {
            http_response_code(422);
            return ['error' => 'Recipient ID and message are required'];
        }

        if ($recipientId === (int) $user['id']) {
            http_response_code(422);
            return ['error' => 'Cannot send message to yourself'];
        }

        // Validate context if provided
        if ($contextId && in_array($contextType, ['campaign', 'event', 'content'], true)) {
            $tableMap = [
                'campaign' => 'campaign_department_campaigns',
                'event' => 'campaign_department_events',
                'content' => 'campaign_department_content_items',
            ];
            $table = $tableMap[$contextType];
            $checkStmt = $this->pdo->prepare("SELECT id FROM {$table} WHERE id = :id");
            $checkStmt->execute(['id' => $contextId]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                return ['error' => ucfirst($contextType) . ' not found'];
            }
        }

        $this->pdo->beginTransaction();
        try {
            // Get or create conversation
            $convStmt = $this->pdo->prepare('
                SELECT id FROM campaign_department_conversations 
                WHERE (participant1_id = :user1 AND participant2_id = :user2)
                   OR (participant1_id = :user2 AND participant2_id = :user1)
            ');
            $convStmt->execute([
                'user1' => (int) $user['id'],
                'user2' => $recipientId,
            ]);
            $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);

            if ($conversation) {
                $conversationId = (int) $conversation['id'];
            } else {
                // Create new conversation
                $insertConv = $this->pdo->prepare('
                    INSERT INTO campaign_department_conversations (participant1_id, participant2_id, last_message_at)
                    VALUES (:user1, :user2, NOW())
                ');
                $insertConv->execute([
                    'user1' => (int) $user['id'],
                    'user2' => $recipientId,
                ]);
                $conversationId = (int) $this->pdo->lastInsertId();
            }

            // Insert message
            $msgStmt = $this->pdo->prepare('
                INSERT INTO campaign_department_messages (
                    conversation_id, sender_id, recipient_id, message_text, 
                    context_type, context_id
                ) VALUES (
                    :conv_id, :sender_id, :recipient_id, :message_text,
                    :context_type, :context_id
                )
            ');
            $msgStmt->execute([
                'conv_id' => $conversationId,
                'sender_id' => (int) $user['id'],
                'recipient_id' => $recipientId,
                'message_text' => $messageText,
                'context_type' => $contextType,
                'context_id' => $contextId,
            ]);
            $messageId = (int) $this->pdo->lastInsertId();

            // Update conversation last_message_at
            $this->pdo->prepare('
                UPDATE campaign_department_conversations SET last_message_at = NOW() WHERE id = :conv_id
            ')->execute(['conv_id' => $conversationId]);

            $this->pdo->commit();

            return [
                'id' => $messageId,
                'conversation_id' => $conversationId,
                'message' => 'Message sent successfully',
            ];
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log('Error sending message: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to send message'];
        }
    }

    /**
     * Mark conversation messages as read
     */
    public function markRead(?array $user, array $params = []): array
    {
            if (!$user) {
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

        $conversationId = (int) ($params['id'] ?? 0);
        $userId = (int) $user['id'];

        // Verify user is part of conversation
        $checkStmt = $this->pdo->prepare('
            SELECT id FROM campaign_department_conversations 
            WHERE id = :conv_id AND (participant1_id = :user_id OR participant2_id = :user_id)
        ');
        $checkStmt->execute(['conv_id' => $conversationId, 'user_id' => $userId]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Conversation not found'];
        }

        $stmt = $this->pdo->prepare('
            UPDATE campaign_department_messages 
            SET is_read = 1, read_at = NOW() 
            WHERE conversation_id = :conv_id AND recipient_id = :user_id AND is_read = 0
        ');
        $stmt->execute(['conv_id' => $conversationId, 'user_id' => $userId]);

        return ['message' => 'Messages marked as read'];
    }
}

