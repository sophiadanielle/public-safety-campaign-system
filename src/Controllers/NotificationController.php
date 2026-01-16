<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use PDO;

class NotificationController
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
     * Get user notifications
     */
    public function index(?array $user, array $params = []): array
    {
        try {
            if (!$user) {
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

            $userId = (int) $user['id'];
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

            $where = ['user_id = :user_id OR user_id IS NULL']; // NULL = system-wide notifications
            $params_array = ['user_id' => $userId];

            if ($unreadOnly) {
                $where[] = 'is_read = 0';
            }

            $whereClause = 'WHERE ' . implode(' AND ', $where);

            $sql = "
                SELECT 
                    id,
                    type,
                    title,
                    message,
                    link_url,
                    icon,
                    is_read,
                    created_at,
                    read_at
                FROM campaign_department_notifications
                $whereClause
                ORDER BY created_at DESC
                LIMIT :limit
            ";

            $stmt = $this->pdo->prepare($sql);
            foreach ($params_array as $key => $value) {
                $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get unread count
            $unreadCount = $this->getUnreadCount($userId);

            return [
                'data' => $notifications,
                'unread_count' => $unreadCount,
            ];
        } catch (\Throwable $e) {
            error_log('NotificationController::index error: ' . $e->getMessage());
            error_log('NotificationController::index stack: ' . $e->getTraceAsString());
            http_response_code(500);
            return ['error' => 'Failed to load notifications', 'data' => [], 'unread_count' => 0];
        }
    }

    /**
     * Mark notification as read
     */
    public function markRead(?array $user, array $params = []): array
    {
        if (!$user) {
            // Don't set http_response_code here - let index.php handle it
            return ['error' => 'Unauthorized'];
        }

        $notificationId = (int) ($params['id'] ?? 0);
        $userId = (int) $user['id'];

        // Verify notification belongs to user or is system-wide
        $stmt = $this->pdo->prepare('SELECT id FROM campaign_department_notifications WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)');
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);

        if (!$stmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Notification not found'];
        }

        $stmt = $this->pdo->prepare('UPDATE campaign_department_notifications SET is_read = 1, read_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $notificationId]);

        return ['message' => 'Notification marked as read'];
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead(?array $user, array $params = []): array
    {
        if (!$user) {
            // Don't set http_response_code here - let index.php handle it
            return ['error' => 'Unauthorized'];
        }

        $userId = (int) $user['id'];

        $stmt = $this->pdo->prepare('UPDATE campaign_department_notifications SET is_read = 1, read_at = NOW() WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);

        return ['message' => 'All notifications marked as read'];
    }

    /**
     * Get unread count
     */
    private function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM campaign_department_notifications WHERE (user_id = :user_id OR user_id IS NULL) AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Create notification (internal method, can be called by other controllers)
     */
    public static function create(PDO $pdo, ?int $userId, string $type, string $title, string $message, ?string $linkUrl = null, ?string $icon = null): int
    {
        $stmt = $pdo->prepare('
            INSERT INTO campaign_department_notifications (user_id, type, title, message, link_url, icon)
            VALUES (:user_id, :type, :title, :message, :link_url, :icon)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link_url' => $linkUrl,
            'icon' => $icon,
        ]);

        return (int) $pdo->lastInsertId();
    }
}

