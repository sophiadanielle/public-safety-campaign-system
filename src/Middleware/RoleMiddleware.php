<?php

declare(strict_types=1);

namespace App\Middleware;

use PDO;
use RuntimeException;

class RoleMiddleware
{
    /**
     * Check if user has required role(s)
     * 
     * @param array|null $user User array from JWT
     * @param array|string $allowedRoles Role name(s) or array of role names
     * @param PDO $pdo Database connection
     * @return bool
     * @throws RuntimeException if user doesn't have required role
     */
    public static function requireRole(?array $user, array|string $allowedRoles, PDO $pdo): bool
    {
        if (!$user || !isset($user['role_id'])) {
            throw new RuntimeException('User not authenticated');
        }

        $allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        $roleId = (int) $user['role_id'];

        // Get user's role name
        $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $roleId]);
        $role = $stmt->fetch();

        if (!$role) {
            throw new RuntimeException('User role not found');
        }

        $userRoleName = $role['name'];

        // Check if user's role is in allowed roles
        if (!in_array($userRoleName, $allowedRoles, true)) {
            throw new RuntimeException('Insufficient permissions');
        }

        return true;
    }

    /**
     * Check if user has specific permission
     * 
     * @param array|null $user User array from JWT
     * @param string $permission Permission name (e.g., 'campaigns.create')
     * @param PDO $pdo Database connection
     * @return bool
     * @throws RuntimeException if user doesn't have permission
     */
    public static function requirePermission(?array $user, string $permission, PDO $pdo): bool
    {
        if (!$user || !isset($user['role_id'])) {
            throw new RuntimeException('User not authenticated');
        }

        $roleId = (int) $user['role_id'];

        // Check if role has permission
        $stmt = $pdo->prepare('
            SELECT COUNT(*) 
            FROM role_permissions rp
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id AND p.name = :permission
        ');
        $stmt->execute(['role_id' => $roleId, 'permission' => $permission]);
        $hasPermission = (int) $stmt->fetchColumn() > 0;

        if (!$hasPermission) {
            throw new RuntimeException('Insufficient permissions: ' . $permission);
        }

        return true;
    }

    /**
     * Get user's role name
     */
    public static function getUserRole(?array $user, PDO $pdo): ?string
    {
        if (!$user || !isset($user['role_id'])) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $user['role_id']]);
        $role = $stmt->fetch();

        return $role ? $role['name'] : null;
    }
}
















