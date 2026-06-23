<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;
use Firebase\JWT\JWT;

require_once __DIR__ . '/../Config/mail_config.php';

class OTPController
{
    private ?PDO $pdo;
    private string $jwtSecret;
    private string $jwtIssuer;
    private string $jwtAudience;
    private int $jwtExpirySeconds;

    public function __construct(
        ?PDO $pdo,
        string $jwtSecret,
        string $jwtIssuer,
        string $jwtAudience,
        int $jwtExpirySeconds
    ) {
        $this->pdo = $pdo;
        $this->jwtSecret = $jwtSecret;
        $this->jwtIssuer = $jwtIssuer;
        $this->jwtAudience = $jwtAudience;
        $this->jwtExpirySeconds = $jwtExpirySeconds;
        $this->ensureOTPTable();
    }

    /**
     * Ensure OTP table exists
     */
    private function ensureOTPTable(): void
    {
        if ($this->pdo === null) return;

        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `campaign_department_otp` (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    otp_code VARCHAR(6) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    is_used TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_otp_email (email),
                    INDEX idx_otp_code (otp_code),
                    INDEX idx_otp_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (PDOException $e) {
            error_log('OTPController: Error creating OTP table: ' . $e->getMessage());
        }
    }

    /**
     * Generate 6-digit OTP
     */
    private function generateOTP(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP after successful password verification
     */
    public function sendOTP(?array $user = null, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(422);
            return ['error' => 'Email and password are required'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            // Verify user credentials first
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, password_hash, user_type, avatar_url, role_id, is_active, archived 
                FROM campaign_department_users 
                WHERE LOWER(TRIM(email)) = :email 
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                http_response_code(401);
                return ['error' => 'Invalid credentials'];
            }

            if ($userData['archived'] == 1) {
                http_response_code(403);
                return ['error' => 'Account has been archived. Please contact administrator.'];
            }

            if ($userData['is_active'] != 1) {
                http_response_code(403);
                return ['error' => 'Account is inactive. Please contact administrator.'];
            }

            // Verify password
            if (!password_verify($password, $userData['password_hash'])) {
                http_response_code(401);
                return ['error' => 'Invalid credentials'];
            }

            // Generate OTP
            $otp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 minute'));

            // Invalidate any existing OTPs for this user
            $stmt = $this->pdo->prepare("UPDATE campaign_department_otp SET is_used = 1 WHERE user_id = :user_id AND is_used = 0");
            $stmt->execute(['user_id' => $userData['id']]);

            // Store new OTP
            $stmt = $this->pdo->prepare("
                INSERT INTO campaign_department_otp (user_id, email, otp_code, expires_at, is_used)
                VALUES (:user_id, :email, :otp_code, :expires_at, 0)
            ");
            $stmt->execute([
                'user_id' => $userData['id'],
                'email' => $userData['email'],
                'otp_code' => $otp,
                'expires_at' => $expiresAt
            ]);

            // Send OTP via email
            $emailSent = \MailConfig::sendOTPEmail(
                $userData['email'],
                $userData['name'] ?? 'User',
                $otp
            );

            if (!$emailSent) {
                error_log('OTP email failed to send to: ' . $userData['email']);
                // For development, still return success but log the OTP
                error_log('DEV OTP for ' . $userData['email'] . ': ' . $otp);
            }

            return [
                'success' => true,
                'message' => 'OTP sent to your email',
                'email' => $this->maskEmail($userData['email']),
                'expires_in' => 60 // 1 minute in seconds
            ];

        } catch (PDOException $e) {
            error_log('OTPController::sendOTP error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to send OTP'];
        }
    }

    /**
     * Verify OTP and complete login
     */
    public function verifyOTP(?array $user = null, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
        $otp = isset($input['otp']) ? trim($input['otp']) : '';

        if (empty($email) || empty($otp)) {
            http_response_code(422);
            return ['error' => 'Email and OTP are required'];
        }

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            http_response_code(422);
            return ['error' => 'Invalid OTP format'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            // Find valid OTP
            $stmt = $this->pdo->prepare("
                SELECT o.*, u.id as uid, u.name, u.email as user_email, u.user_type, u.avatar_url, u.role_id
                FROM campaign_department_otp o
                JOIN campaign_department_users u ON o.user_id = u.id
                WHERE LOWER(TRIM(o.email)) = :email 
                  AND o.otp_code = :otp 
                  AND o.is_used = 0 
                  AND o.expires_at > NOW()
                ORDER BY o.created_at DESC
                LIMIT 1
            ");
            $stmt->execute(['email' => $email, 'otp' => $otp]);
            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRecord) {
                http_response_code(401);
                return ['error' => 'Invalid or expired OTP'];
            }

            // Mark OTP as used
            $stmt = $this->pdo->prepare("UPDATE campaign_department_otp SET is_used = 1 WHERE id = :id");
            $stmt->execute(['id' => $otpRecord['id']]);

            // Generate JWT token
            $token = $this->generateToken(
                (int) $otpRecord['uid'],
                $otpRecord['user_email'],
                (int) $otpRecord['role_id'],
                $otpRecord['name']
            );

            // Set role cookie
            $this->setRoleCookie((int) $otpRecord['role_id']);
            $this->setJWTCookie($token);

            return [
                'success' => true,
                'token' => $token,
                'expires_in' => $this->jwtExpirySeconds,
                'user' => [
                    'id' => (int) $otpRecord['uid'],
                    'name' => $otpRecord['name'],
                    'email' => $otpRecord['user_email'],
                    'user_type' => $otpRecord['user_type'],
                    'avatar_url' => $otpRecord['avatar_url'],
                    'role_id' => (int) $otpRecord['role_id']
                ]
            ];

        } catch (PDOException $e) {
            error_log('OTPController::verifyOTP error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to verify OTP'];
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP(?array $user = null, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';

        if (empty($email)) {
            http_response_code(422);
            return ['error' => 'Email is required'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            // Get user
            $stmt = $this->pdo->prepare("SELECT id, name, email FROM campaign_department_users WHERE LOWER(TRIM(email)) = :email AND is_active = 1 AND archived = 0");
            $stmt->execute(['email' => $email]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                http_response_code(404);
                return ['error' => 'User not found'];
            }

            // Check rate limiting (max 3 OTPs per 10 minutes)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM campaign_department_otp 
                WHERE user_id = :user_id AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute(['user_id' => $userData['id']]);
            $rateCheck = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($rateCheck['count'] >= 3) {
                http_response_code(429);
                return ['error' => 'Too many OTP requests. Please wait 10 minutes.'];
            }

            // Generate new OTP
            $otp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 minute'));

            // Invalidate existing OTPs
            $stmt = $this->pdo->prepare("UPDATE campaign_department_otp SET is_used = 1 WHERE user_id = :user_id AND is_used = 0");
            $stmt->execute(['user_id' => $userData['id']]);

            // Store new OTP
            $stmt = $this->pdo->prepare("
                INSERT INTO campaign_department_otp (user_id, email, otp_code, expires_at, is_used)
                VALUES (:user_id, :email, :otp_code, :expires_at, 0)
            ");
            $stmt->execute([
                'user_id' => $userData['id'],
                'email' => $userData['email'],
                'otp_code' => $otp,
                'expires_at' => $expiresAt
            ]);

            // Send OTP via email
            $emailSent = \MailConfig::sendOTPEmail(
                $userData['email'],
                $userData['name'] ?? 'User',
                $otp
            );

            if (!$emailSent) {
                error_log('DEV OTP for ' . $userData['email'] . ': ' . $otp);
            }

            return [
                'success' => true,
                'message' => 'New OTP sent to your email',
                'expires_in' => 60
            ];

        } catch (PDOException $e) {
            error_log('OTPController::resendOTP error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to resend OTP'];
        }
    }

    /**
     * Mask email for privacy
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;

        $name = $parts[0];
        $domain = $parts[1];

        if (strlen($name) <= 2) {
            $maskedName = $name[0] . '***';
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        }

        return $maskedName . '@' . $domain;
    }

    /**
     * Generate JWT token
     */
    private function generateToken(int $userId, string $email, int $roleId, ?string $name = null): string
    {
        $now = time();
        $payload = [
            'iss' => $this->jwtIssuer,
            'aud' => $this->jwtAudience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->jwtExpirySeconds,
            'sub' => $userId,
            'email' => $email,
            'role_id' => $roleId,
        ];

        if ($name !== null) {
            $payload['name'] = $name;
        }

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Set role cookie
     */
    private function setRoleCookie(int $roleId): void
    {
        $expires = time() + (30 * 24 * 60 * 60);
        setcookie('user_role_id', (string)$roleId, [
            'expires' => $expires,
            'path' => '/',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
        $_COOKIE['user_role_id'] = (string)$roleId;
    }

    /**
     * Set JWT cookie
     */
    private function setJWTCookie(string $token): void
    {
        $expires = time() + (30 * 24 * 60 * 60);
        setcookie('jwt_token', $token, [
            'expires' => $expires,
            'path' => '/',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
        $_COOKIE['jwt_token'] = $token;
    }
}
