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

// Initialize default values to prevent 502 errors
$isViewer = false;
$currentUserRole = null;

try {
    // Get user role - wrapped in try-catch with error suppression to prevent fatal errors
    $oldErrorReporting = error_reporting(0);
    @require_once __DIR__ . '/get_user_role.php';
    error_reporting($oldErrorReporting);
    if (function_exists('getCurrentUserRole')) {
        $currentUserRole = @getCurrentUserRole();
    }
} catch (\Throwable $e) {
    error_log('RBAC block_viewer_access: Failed to get user role: ' . $e->getMessage());
    $currentUserRole = null;
}

// Check if viewer
if ($currentUserRole) {
    $roleLower = strtolower(trim($currentUserRole));
    $isViewer = ($roleLower === 'viewer' || $roleLower === 'partner' || 
                strpos($roleLower, 'partner') !== false || strpos($roleLower, 'viewer') !== false);
}

// Also check cookie directly. For the current deployment, role_id 1 is admin;
// all other roles are read-only/viewer for page rendering.
if (!$currentUserRole && isset($_COOKIE['user_role_id'])) {
    $roleIdFromCookie = (int)($_COOKIE['user_role_id'] ?? 0);
    if ($roleIdFromCookie > 0) {
        $currentUserRole = $roleIdFromCookie === 1 ? 'admin' : 'viewer';
        $isViewer = $currentUserRole === 'viewer';
    }
}

// $isViewer is now available to the page for conditional rendering
// Pages should use PHP conditionals to hide forms and action buttons for viewers
