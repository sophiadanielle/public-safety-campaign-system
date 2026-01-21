<?php
/**
 * Server-side endpoint to set user_role_id cookie from JWT token
 * This ensures the cookie is available to PHP before sidebar renders
 * 
 * Called by JavaScript on page load to sync JWT role_id to cookie
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get role_id from POST or GET
$roleId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $roleId = isset($input['role_id']) ? (int)$input['role_id'] : null;
} else {
    $roleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : null;
}

if (!$roleId || $roleId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'role_id is required']);
    exit;
}

// Validate role_id exists in database
try {
    require_once __DIR__ . '/../src/Config/db_connect.php';
    
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection not available');
    }
    
    $stmt = $pdo->prepare('SELECT id FROM campaign_department_roles WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $roleId]);
    $role = $stmt->fetch();
    
    if (!$role) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid role_id']);
        exit;
    }
    
    // Set cookie with proper settings
    // Expires in 24 hours, available on all paths, SameSite=Lax for CSRF protection
    $expires = time() + (24 * 60 * 60);
    setcookie('user_role_id', (string)$roleId, [
        'expires' => $expires,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => false, // JavaScript needs to read it
        'samesite' => 'Lax'
    ]);
    
    // Also set it in $_COOKIE for immediate use in same request
    $_COOKIE['user_role_id'] = (string)$roleId;
    
    echo json_encode([
        'success' => true,
        'role_id' => $roleId,
        'message' => 'Cookie set successfully'
    ]);
    
} catch (Exception $e) {
    error_log('set_role_cookie.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to set cookie: ' . $e->getMessage()]);
}

