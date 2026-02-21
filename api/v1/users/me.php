<?php
/**
 * User Profile API - Compatible with campaign_users table
 */

// Load JWT library first
if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get JWT token from Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }
    
    $token = $matches[1];
    
    // Decode JWT token
    $jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
    $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
    
    $userId = $decoded->sub;
    
    // Connect to database
    require_once __DIR__ . '/../../../src/Config/db_connect.php';
    
    // Get user from campaign_users table
    $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE id = ? AND archived = 0");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Return user data in format compatible with old API
    echo json_encode([
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['fullname'],
        'fullname' => $user['fullname'],
        'role' => $user['user_type'],
        'user_type' => $user['user_type'],
        'avatar_url' => $user['avatar_url'],
        'phone' => $user['phone'] ?? null,
        'barangay_name' => null,
        'created_at' => $user['date_created']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
