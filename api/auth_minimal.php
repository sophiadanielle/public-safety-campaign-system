<?php
/**
 * Minimal auth test - step by step debugging
 */

// Step 1: Can we output JSON at all?
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load JWT library
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Firebase\JWT\JWT;

// Check action
$action = $_GET['action'] ?? 'login';

if ($action === 'verify-otp') {
    handleVerifyOTP();
    exit;
}

// Step 2: Can we connect to database?
try {
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=LGU;charset=utf8mb4",
        "root",
        "YsqnXk6q#145",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Step 3: Can we get the input?
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Email and password required']);
    exit;
}

// Step 4: Can we query the user?
try {
    $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE email = ? AND archived = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }
    
    // Step 5: Generate OTP
    $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    
    // Step 6: Insert OTP
    $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$user['id']]);
    $stmt = $pdo->prepare("INSERT INTO campaign_otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $email, $otpCode, $expiresAt]);
    
    // Success
    echo json_encode([
        'success' => true,
        'message' => 'OTP generated',
        'user_id' => $user['id'],
        'email' => substr($email, 0, 2) . '***@' . explode('@', $email)[1],
        'otp_for_testing' => $otpCode
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}

function handleVerifyOTP() {
    try {
        $pdo = new PDO(
            "mysql:host=localhost;port=3306;dbname=LGU;charset=utf8mb4",
            "root",
            "YsqnXk6q#145",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = intval($input['user_id'] ?? 0);
        $otpCode = trim($input['otp_code'] ?? '');
        
        if (empty($userId) || empty($otpCode)) {
            echo json_encode(['error' => 'User ID and OTP code are required']);
            return;
        }
        
        // Verify OTP
        $stmt = $pdo->prepare("SELECT * FROM campaign_otp_codes WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId, $otpCode]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$otp) {
            echo json_encode(['error' => 'Invalid or expired OTP code']);
            return;
        }
        
        // Mark OTP as used
        $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE id = ?")->execute([$otp['id']]);
        
        // Get user
        $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Generate JWT token
        $jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
        $payload = [
            'iss' => 'alertaraqc.com',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60),
            'sub' => $user['id'],
            'email' => $user['email'],
            'name' => $user['fullname'],
            'fullname' => $user['fullname'],
            'user_type' => $user['user_type'],
            'role' => $user['user_type'],
            'avatar_url' => $user['avatar_url']
        ];
        
        $token = JWT::encode($payload, $jwtSecret, 'HS256');
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['fullname'],
                'fullname' => $user['fullname'],
                'user_type' => $user['user_type'],
                'role' => $user['user_type'],
                'avatar_url' => $user['avatar_url'],
                'date_created' => $user['date_created']
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['error' => 'Verification failed: ' . $e->getMessage()]);
    }
}
