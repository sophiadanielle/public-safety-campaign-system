<?php

declare(strict_types=1);

namespace App\Middleware;

use PDO;
use RuntimeException;

/**
 * Blocks Viewer role from write operations (POST, PUT, DELETE, PATCH)
 * Viewer = Partner Representative = Read-only access
 */
class ViewerBlockMiddleware
{
    public static function blockViewer(?array $user, PDO $pdo, string $method = 'POST'): void
    {
        if (!$user || !isset($user['role_id'])) {
            return; // Let JWTMiddleware handle auth
        }

        // Only block write methods
        $writeMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array(strtoupper($method), $writeMethods, true)) {
            return; // Allow GET requests
        }

        $roleId = (int) $user['role_id'];

        // Get user's role name
        $stmt = $pdo->prepare('SELECT name FROM campaign_department_roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            return; // Role not found, let other middleware handle
        }

        $roleName = strtolower(trim($role['name']));

        // Check if user is Viewer/Partner
        $isViewer = ($roleName === 'viewer' || 
                    $roleName === 'partner' || 
                    $roleName === 'partner representative' ||
                    strpos($roleName, 'partner') !== false ||
                    strpos($roleName, 'viewer') !== false);

        if ($isViewer) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Forbidden: Viewer role is read-only. Partner Representatives cannot create, edit, or delete content.',
                'message' => 'This operation requires LGU personnel access. Please contact your administrator if you need assistance.'
            ]);
            exit;
        }
    }

    /**
     * Check if user is Viewer role
     */
    public static function isViewer(?array $user, ?PDO $pdo): bool
    {
        if (!$user || !isset($user['role_id']) || !$pdo) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('SELECT name FROM campaign_department_roles WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $user['role_id']]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                return false;
            }

            $roleName = strtolower(trim($role['name']));
            return ($roleName === 'viewer' || 
                   $roleName === 'partner' || 
                   $roleName === 'partner representative' ||
                   strpos($roleName, 'partner') !== false ||
                   strpos($roleName, 'viewer') !== false);
        } catch (\Exception $e) {
            error_log('ViewerBlockMiddleware::isViewer error: ' . $e->getMessage());
            return false;
        }
    }
}


