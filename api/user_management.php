<?php
/**
 * User Management API
 * Handles CRUD operations for campaign_users table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../src/Config/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';

function verifyToken($jwtSecret) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    
    try {
        $token = $matches[1];
        return JWT::decode($token, new Key($jwtSecret, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

$user = verifyToken($jwtSecret);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'me' && $user) {
    handleGetCurrentUser($pdo, $user);
    exit;
}

if (!$user || !in_array($user->user_type, ['Super Admin', 'Admin'])) {
    if ($method !== 'GET' || $action !== 'me') {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin privileges required.']);
        exit;
    }
}

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            handleListUsers($pdo);
        } elseif ($action === 'archived') {
            handleListArchivedUsers($pdo);
        } elseif (isset($_GET['id'])) {
            handleGetUser($pdo, intval($_GET['id']));
        } else {
            handleListUsers($pdo);
        }
        break;
    case 'POST':
        handleCreateUser($pdo);
        break;
    case 'PUT':
        handleUpdateUser($pdo);
        break;
    case 'DELETE':
        handleArchiveUser($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetCurrentUser($pdo, $tokenUser) {
    try {
        $stmt = $pdo->prepare("SELECT id, email, fullname, user_type, avatar_url, date_created FROM campaign_users WHERE id = ? AND archived = 0");
        $stmt->execute([$tokenUser->sub]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        echo json_encode(['user' => $user]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}

function handleListUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, email, fullname, user_type, avatar_url, date_created FROM campaign_users WHERE archived = 0 ORDER BY date_created DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['users' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}

function handleListArchivedUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, email, fullname, user_type, avatar_url, date_created FROM campaign_users WHERE archived = 1 ORDER BY date_created DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['users' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}

function handleGetUser($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT id, email, fullname, user_type, avatar_url, date_created, archived FROM campaign_users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        echo json_encode(['user' => $user]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}

function handleCreateUser($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $fullname = trim($input['fullname'] ?? '');
    $userType = $input['user_type'] ?? 'Staff';
    $avatarUrl = $input['avatar_url'] ?? null;
    
    if (empty($email) || empty($password) || empty($fullname)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email, password, and fullname are required']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }
    
    $validTypes = ['Super Admin', 'Admin', 'Staff', 'Employee'];
    if (!in_array($userType, $validTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user type']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM campaign_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO campaign_users (email, password, fullname, user_type, avatar_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashedPassword, $fullname, $userType, $avatarUrl]);
        
        $userId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user' => [
                'id' => $userId,
                'email' => $email,
                'fullname' => $fullname,
                'user_type' => $userType,
                'avatar_url' => $avatarUrl
            ]
        ]);
    } catch (Exception $e) {
        error_log("Create user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}

function handleUpdateUser($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? $_GET['id'] ?? 0);
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE id = ?");
        $stmt->execute([$id]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingUser) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        $email = trim($input['email'] ?? $existingUser['email']);
        $fullname = trim($input['fullname'] ?? $existingUser['fullname']);
        $userType = $input['user_type'] ?? $existingUser['user_type'];
        $avatarUrl = $input['avatar_url'] ?? $existingUser['avatar_url'];
        $archived = isset($input['archived']) ? intval($input['archived']) : $existingUser['archived'];
        
        if (!empty($input['email']) && $input['email'] !== $existingUser['email']) {
            $stmt = $pdo->prepare("SELECT id FROM campaign_users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }
        }
        
        $sql = "UPDATE campaign_users SET email = ?, fullname = ?, user_type = ?, avatar_url = ?, archived = ?";
        $params = [$email, $fullname, $userType, $avatarUrl, $archived];
        
        if (!empty($input['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => [
                'id' => $id,
                'email' => $email,
                'fullname' => $fullname,
                'user_type' => $userType,
                'avatar_url' => $avatarUrl,
                'archived' => $archived
            ]
        ]);
    } catch (Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}

function handleArchiveUser($pdo) {
    $id = intval($_GET['id'] ?? 0);
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE campaign_users SET archived = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'User archived successfully'
        ]);
    } catch (Exception $e) {
        error_log("Archive user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
}
