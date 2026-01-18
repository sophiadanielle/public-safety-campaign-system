<?php
/**
 * RBAC: Check if current user is Viewer role
 * Sets $isViewer variable for use in pages to conditionally hide forms/action buttons
 * 
 * Viewers can access pages to VIEW approved content (read-only)
 * But forms, create/edit buttons, and internal workflows are hidden
 * 
 * Call this at the TOP of any public PHP page that needs role-aware rendering
 */

// Get user role
require_once __DIR__ . '/get_user_role.php';
$currentUserRole = getCurrentUserRole();

// Check if viewer
$isViewer = false;
if ($currentUserRole) {
    $roleLower = strtolower(trim($currentUserRole));
    $isViewer = ($roleLower === 'viewer' || $roleLower === 'partner' || 
                strpos($roleLower, 'partner') !== false || strpos($roleLower, 'viewer') !== false);
}

// Also check cookie directly
if (!$isViewer && isset($_COOKIE['user_role_id'])) {
    $roleIdFromCookie = (int)($_COOKIE['user_role_id'] ?? 0);
    if ($roleIdFromCookie > 0) {
        try {
            require_once __DIR__ . '/../../src/Config/db_connect.php';
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare('SELECT name FROM campaign_department_roles WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $roleIdFromCookie]);
                $roleResult = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $roleName = strtolower(trim($roleResult['name']));
                    $isViewer = ($roleName === 'viewer' || $roleName === 'partner' || 
                                strpos($roleName, 'partner') !== false || strpos($roleName, 'viewer') !== false ||
                                $roleIdFromCookie === 6);
                }
            }
        } catch (Exception $e) {
            error_log('RBAC block_viewer_access: Error checking role from cookie: ' . $e->getMessage());
        }
    }
}

// $isViewer is now available to the page for conditional rendering
// Pages should use PHP conditionals to hide forms and action buttons for viewers

