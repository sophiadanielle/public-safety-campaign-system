<?php
/**
 * OTP Authentication API
 * Handles login with OTP verification using campaign_users table
 */

// Suppress all errors and warnings to prevent breaking JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../src/Config/db_connect.php';
    
    // Check if vendor autoload exists
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
}

use Firebase\JWT\JWT;

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($pdo);
        break;
    case 'verify-otp':
        handleVerifyOTP($pdo);
        break;
    case 'resend-otp':
        handleResendOTP($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function handleLogin($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE email = ? AND archived = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password']);
            return;
        }
        
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password']);
            return;
        }
        
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$user['id']]);
        
        $stmt = $pdo->prepare("INSERT INTO campaign_otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $email, $otpCode, $expiresAt]);
        
        // Try to send OTP email if MailService is available
        try {
            if (class_exists('App\Services\MailService')) {
                $mailService = new \App\Services\MailService();
                $emailSent = $mailService->sendOTP($email, $user['fullname'], $otpCode);
                
                if (!$emailSent) {
                    error_log("Failed to send OTP email to: $email");
                }
            } else {
                error_log("MailService not available - OTP: $otpCode for $email");
            }
        } catch (Exception $e) {
            error_log("Mail service error: " . $e->getMessage() . " - OTP: $otpCode");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent to your email',
            'requires_otp' => true,
            'email' => maskEmail($email),
            'user_id' => $user['id']
        ]);
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error. Please try again.']);
    }
}

function handleVerifyOTP($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = intval($input['user_id'] ?? 0);
    $otpCode = trim($input['otp_code'] ?? '');
    
    if (empty($userId) || empty($otpCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID and OTP code are required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM campaign_otp_codes 
            WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId, $otpCode]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$otp) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired OTP code']);
            return;
        }
        
        $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE id = ?")->execute([$otp['id']]);
        
        $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Generate JWT token with correct iss and aud claims to match JWTMiddleware
        $jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
        $jwtIssuer = 'public-safety-campaign-system';
        $jwtAudience = 'public-safety-campaign-system';
        $now = time();
        $payload = [
            'iss' => $jwtIssuer,
            'aud' => $jwtAudience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (24 * 60 * 60),
            'sub' => $user['id'],
            'email' => $user['email'],
            'name' => $user['fullname'],
            'fullname' => $user['fullname'],
            'user_type' => $user['user_type'],
            'role_id' => 0,
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
                'fullname' => $user['fullname'],
                'user_type' => $user['user_type'],
                'avatar_url' => $user['avatar_url'],
                'date_created' => $user['date_created']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error. Please try again.']);
    }
}

function handleResendOTP($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = intval($input['user_id'] ?? 0);
    
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE id = ? AND archived = 0");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$userId]);
        
        $stmt = $pdo->prepare("INSERT INTO campaign_otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $user['email'], $otpCode, $expiresAt]);
        
        // Try to send OTP email if MailService is available
        try {
            if (class_exists('App\Services\MailService')) {
                $mailService = new \App\Services\MailService();
                $emailSent = $mailService->sendOTP($user['email'], $user['fullname'], $otpCode);
                
                if (!$emailSent) {
                    error_log("Failed to resend OTP email to: " . $user['email']);
                }
            } else {
                error_log("MailService not available - Resend OTP: $otpCode for " . $user['email']);
            }
        } catch (Exception $e) {
            error_log("Mail service error on resend: " . $e->getMessage() . " - OTP: $otpCode");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'New OTP sent to your email',
            'email' => maskEmail($user['email'])
        ]);
        
    } catch (PDOException $e) {
        error_log("Resend OTP error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error. Please try again.']);
    }
}

function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    
    $name = $parts[0];
    $domain = $parts[1];
    
    if (strlen($name) <= 2) {
        $masked = $name[0] . '***';
    } else {
        $masked = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
    }
    
    return $masked . '@' . $domain;
}
