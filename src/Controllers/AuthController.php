<?php

declare(strict_types=1);

namespace App\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use Throwable;

class AuthController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
    }

    public function login(?array $user = null, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $rawEmail = isset($input['email']) ? trim($input['email']) : '';
        $rawPassword = isset($input['password']) ? (string)$input['password'] : '';

        // Log for debugging (remove in production)
        error_log('Login attempt - Email: ' . $rawEmail . ', Password length: ' . strlen($rawPassword));

        if (empty($rawEmail) || empty($rawPassword)) {
            http_response_code(422);
            return ['error' => 'Email and password are required.'];
        }

        // Validate email format
        $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(422);
            return ['error' => 'Invalid email format.'];
        }

        // Normalize email for comparison (case-insensitive, trimmed)
        $normalizedEmail = strtolower(trim($email));
        $normalizedPassword = trim($rawPassword);
        
        // Developer shortcut: allow a guaranteed local admin login even if DB seeding is broken.
        // This ONLY kicks in for the known demo credentials.
        // Admin credentials: admin@barangay1.qc.gov.ph / pass123
        $isAdminEmail = ($normalizedEmail === 'admin@barangay1.qc.gov.ph');
        $isAdminPassword = ($normalizedPassword === 'pass123' || $rawPassword === 'pass123');
        
        if ($isAdminEmail && $isAdminPassword) {
            $demoUser = [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@barangay1.qc.gov.ph',
                'role_id' => 1,
                'barangay_id' => 1,
            ];

            $token = $this->generateToken($demoUser['id'], $demoUser['email'], $demoUser['role_id']);

            return [
                'token' => $token,
                'expires_in' => $this->jwtExpirySeconds,
                'user' => $demoUser,
            ];
        }
        
        // Normalize email for database query (case-insensitive)
        $stmt = $this->pdo->prepare('SELECT id, name, email, password_hash, role_id, barangay_id FROM users WHERE LOWER(TRIM(email)) = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $normalizedEmail]);
        $user = $stmt->fetch();

        // If login fails for the default admin, attempt an automatic repair of that account
        if (!$user || !password_verify($rawPassword, $user['password_hash'] ?? '')) {
            $repairedUser = $this->maybeRepairAdminUser($email, $rawPassword);
            if ($repairedUser) {
                $user = $repairedUser;
            } else {
                http_response_code(401);
                return ['error' => 'Invalid credentials'];
            }
        }

        $token = $this->generateToken((int) $user['id'], $user['email'], (int) $user['role_id']);

        return [
            'token' => $token,
            'expires_in' => $this->jwtExpirySeconds,
            'user' => $this->publicUser($user),
        ];
    }

    /**
     * Simple registration endpoint used by the public signup page.
     * For now this creates an active user and immediately returns a JWT.
     */
    public function register(?array $user = null, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $name = isset($input['name']) ? trim((string) $input['name']) : '';
        $email = isset($input['email']) ? filter_var($input['email'], FILTER_VALIDATE_EMAIL) : null;
        $password = $input['password'] ?? '';

        if (!$name || !$email || !$password) {
            http_response_code(422);
            return ['error' => 'Name, email, and password are required.'];
        }

        // Ensure email is unique
        $check = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            http_response_code(409);
            return ['error' => 'An account with that email already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // For now, default new signups to role_id = 1 and barangay_id = 1 so they can log in.
        $roleId = 1;
        $barangayId = 1;

        $stmt = $this->pdo->prepare('
            INSERT INTO users (role_id, barangay_id, name, email, password_hash, is_active)
            VALUES (:role_id, :barangay_id, :name, :email, :password_hash, 1)
        ');

        $stmt->execute([
            'role_id' => $roleId,
            'barangay_id' => $barangayId,
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        $userId = (int) $this->pdo->lastInsertId();

        $token = $this->generateToken($userId, $email, $roleId);

        return [
            'token' => $token,
            'expires_in' => $this->jwtExpirySeconds,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role_id' => $roleId,
                'barangay_id' => $barangayId,
            ],
        ];
    }

    public function refresh(?array $user = null, array $params = []): array
    {
        $token = $this->getBearerToken();
        if (!$token) {
            http_response_code(401);
            return ['error' => 'Authorization token missing'];
        }

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $userId = (int) ($decoded->sub ?? 0);
            if ($userId <= 0) {
                throw new \RuntimeException('Invalid token subject');
            }

            $stmt = $this->pdo->prepare('SELECT id, name, email, role_id, barangay_id FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            $newToken = $this->generateToken((int) $user['id'], $user['email'], (int) $user['role_id']);

            return [
                'token' => $newToken,
                'expires_in' => $this->jwtExpirySeconds,
                'user' => $this->publicUser($user),
            ];
        } catch (Throwable $e) {
            http_response_code(401);
            return ['error' => 'Token refresh failed: ' . $e->getMessage()];
        }
    }

    public function me(?array $user, array $params = []): array
    {
        try {
            if (!$user) {
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

            // Fetch full user data with barangay name
            $userId = (int) $user['id'];
            $stmt = $this->pdo->prepare('
                SELECT u.*, b.name as barangay_name 
                FROM users u 
                LEFT JOIN barangays b ON b.id = u.barangay_id 
                WHERE u.id = :id
            ');
            $stmt->execute(['id' => $userId]);
            $fullUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fullUser) {
                return ['user' => $this->publicUser($fullUser)];
            }

            return ['user' => $this->publicUser($user)];
        } catch (\Throwable $e) {
            error_log('AuthController::me error: ' . $e->getMessage());
            error_log('AuthController::me stack: ' . $e->getTraceAsString());
            http_response_code(500);
            return ['error' => 'Failed to load user data'];
        }
    }

    private function generateToken(int $userId, string $email, int $roleId): string
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

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
        return null;
    }

    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
            'barangay_id' => isset($user['barangay_id']) ? (int) $user['barangay_id'] : null,
            'barangay_name' => $user['barangay_name'] ?? null,
            'phone_number' => $user['phone_number'] ?? $user['phone'] ?? null,
            'created_at' => $user['created_at'] ?? null,
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = (int) $user['id'];

        $updates = [];
        $params_array = ['id' => $userId];

        if (isset($input['name'])) {
            $updates[] = 'name = :name';
            $params_array['name'] = trim($input['name']);
        }

        if (isset($input['email'])) {
            $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                http_response_code(422);
                return ['error' => 'Invalid email format'];
            }
            // Check if email is already taken by another user
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
            $stmt->execute(['email' => $email, 'id' => $userId]);
            if ($stmt->fetch()) {
                http_response_code(422);
                return ['error' => 'Email already in use'];
            }
            $updates[] = 'email = :email';
            $params_array['email'] = $email;
        }

        if (isset($input['phone'])) {
            $updates[] = 'phone = :phone';
            $params_array['phone'] = trim($input['phone']) ?: null;
        }

        if (empty($updates)) {
            http_response_code(422);
            return ['error' => 'No fields to update'];
        }

        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params_array);

        // Fetch updated user
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

        return ['user' => $this->publicUser($updatedUser), 'message' => 'Profile updated successfully'];
    }

    /**
     * Change user password
     */
    public function changePassword(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $userId = (int) $user['id'];

        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            http_response_code(422);
            return ['error' => 'Current password and new password are required'];
        }

        if (strlen($newPassword) < 6) {
            http_response_code(422);
            return ['error' => 'New password must be at least 6 characters'];
        }

        // Verify current password
        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            http_response_code(404);
            return ['error' => 'User not found'];
        }

        if (!password_verify($currentPassword, $userData['password'])) {
            http_response_code(422);
            return ['error' => 'Current password is incorrect'];
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);

        return ['message' => 'Password changed successfully'];
    }

    /**
     * Best-effort automatic repair for the default admin account.
     * This avoids "Invalid credentials" when the DB seed is out of sync.
     */
    private function maybeRepairAdminUser(string $email, string $password): ?array
    {
        // Only ever auto-repair for the known default admin account
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail !== 'admin@barangay1.qc.gov.ph') {
            return null;
        }
        
        // Also check if password matches admin password (pass123)
        $normalizedPassword = trim($password);
        if ($normalizedPassword !== 'pass123' && $password !== 'pass123') {
            return null;
        }

        // Ensure supporting tables exist (no-op if already there)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                description VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS barangays (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL UNIQUE,
                city VARCHAR(150) NULL,
                province VARCHAR(150) NULL,
                region VARCHAR(150) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role_id INT UNSIGNED NOT NULL,
                barangay_id INT UNSIGNED NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
                CONSTRAINT fk_users_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Ensure base role and barangay exist
        $this->pdo->exec("
            INSERT IGNORE INTO roles (id, name, description)
            VALUES (1, 'Barangay Administrator', 'Full access to all campaign management features')
        ");

        $this->pdo->exec("
            INSERT IGNORE INTO barangays (id, name, city, province, region)
            VALUES (1, 'Barangay 1', 'Quezon City', 'Metro Manila', 'NCR')
        ");

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Check if user already exists
        $checkStmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email OR id = 1 LIMIT 1');
        $checkStmt->execute(['email' => $email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Update existing admin user with the new password
            $update = $this->pdo->prepare("
                UPDATE users
                SET password_hash = :password_hash,
                    role_id = 1,
                    barangay_id = 1,
                    name = 'Admin User',
                    is_active = 1
                WHERE id = :id
            ");
            $update->execute([
                'password_hash' => $passwordHash,
                'id' => $existing['id'],
            ]);
        } else {
            // Create fresh admin user
            $insert = $this->pdo->prepare("
                INSERT INTO users (id, role_id, barangay_id, name, email, password_hash, is_active)
                VALUES (1, 1, 1, 'Admin User', :email, :password_hash, 1)
            ");
            $insert->execute([
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
        }

        // Reload the user record
        $reload = $this->pdo->prepare('SELECT id, name, email, password_hash, role_id, barangay_id FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $reload->execute(['email' => $email]);
        $user = $reload->fetch();

        if (!$user) {
            return null;
        }

        // Final safety check
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }
}

