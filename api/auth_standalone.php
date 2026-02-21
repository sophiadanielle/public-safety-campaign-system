<?php
/**
 * Standalone OTP Authentication API
 * Does NOT use db_connect.php to avoid debug output issues
 */

// Suppress ALL errors
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Direct database connection without db_connect.php
try {
    // Load .env file manually
    $envPath = dirname(__DIR__) . '/.env';
    
    // Check if running in production (based on server name)
    $isProduction = isset($_SERVER['SERVER_NAME']) && 
                    (strpos($_SERVER['SERVER_NAME'], 'alertaraqc.com') !== false ||
                     strpos($_SERVER['SERVER_NAME'], '72.60.209.226') !== false);
    
    // Default values for local development
    $dbHost = 'localhost';
    $dbName = 'LGU';
    $dbUser = 'root';
    $dbPass = '';  // Empty for local XAMPP
    $dbPort = '3306';
    
    // Try to load from .env file
    if (file_exists($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
                
                $pos = strpos($line, '=');
                if ($pos === false) continue;
                
                $key = trim(substr($line, 0, $pos));
                $val = trim(substr($line, $pos + 1));
                
                // Remove quotes if present
                if (strlen($val) > 1 && 
                    (($val[0] === '"' && substr($val, -1) === '"') || 
                     ($val[0] === "'" && substr($val, -1) === "'"))) {
                    $val = substr($val, 1, -1);
                }
                
                // Use production credentials if on production server
                if ($isProduction) {
                    if ($key === 'PROD_DB_HOST') $dbHost = $val;
                    if ($key === 'PROD_DB_NAME') $dbName = $val;
                    if ($key === 'PROD_DB_USER') $dbUser = $val;
                    if ($key === 'PROD_DB_PASS') $dbPass = $val;
                    if ($key === 'PROD_DB_PORT') $dbPort = $val;
                } else {
                    // Use local credentials
                    if ($key === 'DB_HOST') $dbHost = $val;
                    if ($key === 'DB_NAME') $dbName = $val;
                    if ($key === 'DB_USER') $dbUser = $val;
                    if ($key === 'DB_PASSWORD') $dbPass = $val;
                    if ($key === 'DB_PORT') $dbPort = $val;
                }
            }
        }
    }
    
    // Create PDO connection
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

// Load JWT library and MailService
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Firebase\JWT\JWT;
use App\Services\MailService;

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
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM campaign_users WHERE email = ? AND archived = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password']);
            return;
        }
        
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $pdo->prepare("UPDATE campaign_otp_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0")->execute([$user['id']]);
        $stmt = $pdo->prepare("INSERT INTO campaign_otp_codes (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $email, $otpCode, $expiresAt]);
        
        // Send OTP via email
        $emailSent = false;
        try {
            $mailService = new MailService();
            $emailSent = $mailService->sendOTP($email, $user['fullname'] ?? 'User', $otpCode);
        } catch (Exception $e) {
            error_log('Mail service error: ' . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent to your email',
            'requires_otp' => true,
            'email' => maskEmail($email),
            'user_id' => $user['id']
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Server error',
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
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
        $stmt = $pdo->prepare("SELECT * FROM campaign_otp_codes WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
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
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
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
        
        // Send OTP via email
        $emailSent = false;
        try {
            $mailService = new MailService();
            $emailSent = $mailService->sendOTP($user['email'], $user['fullname'] ?? 'User', $otpCode);
        } catch (Exception $e) {
            error_log('Mail service error on resend: ' . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'New OTP sent to your email',
            'email' => maskEmail($user['email'])
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
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
