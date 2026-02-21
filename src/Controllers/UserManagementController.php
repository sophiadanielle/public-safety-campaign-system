<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

class UserManagementController
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableStructure();
    }

    /**
     * Ensure required columns exist in campaign_department_users table
     */
    private function ensureTableStructure(): void
    {
        if ($this->pdo === null) return;

        try {
            // Check and add user_type column
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `campaign_department_users` LIKE 'user_type'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE `campaign_department_users` ADD COLUMN `user_type` ENUM('Super Admin', 'Admin', 'Staff', 'Employee') DEFAULT 'Employee' AFTER `name`");
            }

            // Check and add avatar_url column
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `campaign_department_users` LIKE 'avatar_url'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE `campaign_department_users` ADD COLUMN `avatar_url` VARCHAR(500) NULL AFTER `user_type`");
            }

            // Check and add archived column
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `campaign_department_users` LIKE 'archived'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE `campaign_department_users` ADD COLUMN `archived` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`");
            }

            // Check and add archived_at column
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `campaign_department_users` LIKE 'archived_at'");
            if ($stmt->rowCount() === 0) {
                $this->pdo->exec("ALTER TABLE `campaign_department_users` ADD COLUMN `archived_at` DATETIME NULL AFTER `archived`");
            }

            // Create OTP table if not exists
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
            error_log('UserManagementController: Error ensuring table structure: ' . $e->getMessage());
        }
    }

    /**
     * List all users (non-archived)
     */
    public function listUsers(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            $showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
            
            $sql = "SELECT id, name, email, user_type, avatar_url, is_active, archived, created_at, updated_at 
                    FROM campaign_department_users 
                    WHERE archived = :archived 
                    ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['archived' => $showArchived ? 1 : 0]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['data' => $users, 'count' => count($users)];
        } catch (PDOException $e) {
            error_log('UserManagementController::listUsers error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to fetch users'];
        }
    }

    /**
     * Get single user by ID
     */
    public function getUser(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $userId = (int) ($params['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid user ID'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, name, email, user_type, avatar_url, is_active, archived, created_at, updated_at 
                                          FROM campaign_department_users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                http_response_code(404);
                return ['error' => 'User not found'];
            }

            return ['data' => $userData];
        } catch (PDOException $e) {
            error_log('UserManagementController::getUser error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to fetch user'];
        }
    }

    /**
     * Create new user
     */
    public function createUser(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $email = isset($input['email']) ? filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL) : null;
        $password = $input['password'] ?? '';
        $name = isset($input['name']) ? trim($input['name']) : '';
        $userType = $input['user_type'] ?? 'Employee';

        if (!$email) {
            http_response_code(422);
            return ['error' => 'Valid email is required'];
        }

        if (empty($password) || strlen($password) < 6) {
            http_response_code(422);
            return ['error' => 'Password must be at least 6 characters'];
        }

        if (empty($name)) {
            http_response_code(422);
            return ['error' => 'Name is required'];
        }

        $validUserTypes = ['Super Admin', 'Admin', 'Staff', 'Employee'];
        if (!in_array($userType, $validUserTypes, true)) {
            http_response_code(422);
            return ['error' => 'Invalid user type. Must be: ' . implode(', ', $validUserTypes)];
        }

        try {
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM campaign_department_users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                http_response_code(409);
                return ['error' => 'Email already exists'];
            }

            // Map user_type to role_id
            $roleMapping = [
                'Super Admin' => 1,
                'Admin' => 1,
                'Staff' => 2,
                'Employee' => 2
            ];
            $roleId = $roleMapping[$userType] ?? 2;

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("
                INSERT INTO campaign_department_users (role_id, name, email, password_hash, user_type, is_active, archived, created_at)
                VALUES (:role_id, :name, :email, :password_hash, :user_type, 1, 0, NOW())
            ");

            $stmt->execute([
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'user_type' => $userType
            ]);

            $newUserId = (int) $this->pdo->lastInsertId();

            return [
                'message' => 'User created successfully',
                'data' => [
                    'id' => $newUserId,
                    'name' => $name,
                    'email' => $email,
                    'user_type' => $userType
                ]
            ];
        } catch (PDOException $e) {
            error_log('UserManagementController::createUser error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to create user'];
        }
    }

    /**
     * Update user
     */
    public function updateUser(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $userId = (int) ($params['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid user ID'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id FROM campaign_department_users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                return ['error' => 'User not found'];
            }

            $updates = [];
            $params_array = ['id' => $userId];

            if (isset($input['name']) && trim($input['name']) !== '') {
                $updates[] = 'name = :name';
                $params_array['name'] = trim($input['name']);
            }

            if (isset($input['email'])) {
                $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    http_response_code(422);
                    return ['error' => 'Invalid email format'];
                }
                // Check if email is taken by another user
                $stmt = $this->pdo->prepare("SELECT id FROM campaign_department_users WHERE email = :email AND id != :user_id");
                $stmt->execute(['email' => $email, 'user_id' => $userId]);
                if ($stmt->fetch()) {
                    http_response_code(409);
                    return ['error' => 'Email already in use'];
                }
                $updates[] = 'email = :email';
                $params_array['email'] = $email;
            }

            if (isset($input['password']) && strlen($input['password']) >= 6) {
                $updates[] = 'password_hash = :password_hash';
                $params_array['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            if (isset($input['user_type'])) {
                $validUserTypes = ['Super Admin', 'Admin', 'Staff', 'Employee'];
                if (in_array($input['user_type'], $validUserTypes, true)) {
                    $updates[] = 'user_type = :user_type';
                    $params_array['user_type'] = $input['user_type'];
                }
            }

            if (isset($input['avatar_url'])) {
                $updates[] = 'avatar_url = :avatar_url';
                $params_array['avatar_url'] = $input['avatar_url'];
            }

            if (empty($updates)) {
                http_response_code(422);
                return ['error' => 'No valid fields to update'];
            }

            $sql = 'UPDATE campaign_department_users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params_array);

            return ['message' => 'User updated successfully'];
        } catch (PDOException $e) {
            error_log('UserManagementController::updateUser error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to update user'];
        }
    }

    /**
     * Archive user (soft delete)
     */
    public function archiveUser(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $userId = (int) ($params['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid user ID'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE campaign_department_users SET archived = 1, archived_at = NOW(), is_active = 0 WHERE id = :id");
            $stmt->execute(['id' => $userId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return ['error' => 'User not found'];
            }

            return ['message' => 'User archived successfully'];
        } catch (PDOException $e) {
            error_log('UserManagementController::archiveUser error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to archive user'];
        }
    }

    /**
     * Restore archived user
     */
    public function restoreUser(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $userId = (int) ($params['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid user ID'];
        }

        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable'];
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE campaign_department_users SET archived = 0, archived_at = NULL, is_active = 1 WHERE id = :id");
            $stmt->execute(['id' => $userId]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                return ['error' => 'User not found'];
            }

            return ['message' => 'User restored successfully'];
        } catch (PDOException $e) {
            error_log('UserManagementController::restoreUser error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to restore user'];
        }
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $userId = (int) ($params['id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid user ID'];
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return ['error' => 'No file uploaded or upload error'];
        }

        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes, true)) {
            http_response_code(422);
            return ['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP'];
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            http_response_code(422);
            return ['error' => 'File too large. Maximum size: 5MB'];
        }

        try {
            $uploadDir = __DIR__ . '/../../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                http_response_code(500);
                return ['error' => 'Failed to save file'];
            }

            $avatarUrl = '/uploads/avatars/' . $filename;

            $stmt = $this->pdo->prepare("UPDATE campaign_department_users SET avatar_url = :avatar_url WHERE id = :id");
            $stmt->execute(['avatar_url' => $avatarUrl, 'id' => $userId]);

            return ['message' => 'Avatar uploaded successfully', 'avatar_url' => $avatarUrl];
        } catch (PDOException $e) {
            error_log('UserManagementController::uploadAvatar error: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to update avatar'];
        }
    }
}
