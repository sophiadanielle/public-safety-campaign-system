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
        private ?PDO $pdo,
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
        
        // Developer shortcut: allow a guaranteed local admin login even if DB seeding is broken or DB is unavailable.
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

            $token = $this->generateToken($demoUser['id'], $demoUser['email'], $demoUser['role_id'], $demoUser['name']);

            return [
                'token' => $token,
                'expires_in' => $this->jwtExpirySeconds,
                'user' => $demoUser,
            ];
        }
        
        // If PDO is null (database connection failed), only allow demo login
        if ($this->pdo === null) {
            http_response_code(503);
            return ['error' => 'Database unavailable. Please use demo credentials: admin@barangay1.qc.gov.ph / pass123'];
        }
        
        // Normalize email for database query (case-insensitive)
        try {
            $stmt = $this->pdo->prepare('SELECT id, name, email, password_hash, role_id, barangay_id FROM `campaign_department_users` WHERE LOWER(TRIM(email)) = :email AND is_active = 1 LIMIT 1');
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

            $token = $this->generateToken((int) $user['id'], $user['email'], (int) $user['role_id'], $user['name'] ?? null);

            return [
                'token' => $token,
                'expires_in' => $this->jwtExpirySeconds,
                'user' => $this->publicUser($user),
            ];
        } catch (\PDOException $e) {
            error_log('Database error during login: ' . $e->getMessage());
            // Fall back to demo login if database query fails
            if ($isAdminEmail && $isAdminPassword) {
                $demoUser = [
                    'id' => 1,
                    'name' => 'Admin User',
                    'email' => 'admin@barangay1.qc.gov.ph',
                    'role_id' => 1,
                    'barangay_id' => 1,
                ];
                $token = $this->generateToken($demoUser['id'], $demoUser['email'], $demoUser['role_id'], $demoUser['name']);
                return [
                    'token' => $token,
                    'expires_in' => $this->jwtExpirySeconds,
                    'user' => $demoUser,
                ];
            }
            http_response_code(503);
            return ['error' => 'Database error. Please try again or use demo credentials: admin@barangay1.qc.gov.ph / pass123'];
        }
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
        $roleName = isset($input['role']) ? trim((string) $input['role']) : '';

        if (!$name || !$email || !$password) {
            http_response_code(422);
            return ['error' => 'Name, email, and password are required.'];
        }

        // RBAC: Role selection is REQUIRED - no auto-assignment
        // Official user roles from research defense: admin, captain, kagawad, secretary, staff, viewer
        if (!$roleName) {
            http_response_code(422);
            return ['error' => 'Role selection is required. Please select your role: admin, captain, kagawad, secretary, staff, or viewer.'];
        }

        // Validate role name against allowed LGU roles (official defense roles)
        $allowedRoles = ['admin', 'captain', 'kagawad', 'secretary', 'staff', 'viewer'];
        // Also allow 'partner' as legacy mapping to 'viewer'
        $normalizedRoleName = strtolower(trim($roleName));
        
        // Map legacy 'partner' role to 'viewer'
        if ($normalizedRoleName === 'partner') {
            $normalizedRoleName = 'viewer';
        }
        
        if (!in_array($normalizedRoleName, $allowedRoles, true)) {
            http_response_code(422);
            return ['error' => 'Invalid role. Allowed roles: admin, captain, kagawad, secretary, staff, viewer.'];
        }

        // Ensure email is unique
        $check = $this->pdo->prepare('SELECT id FROM campaign_department_users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            http_response_code(409);
            return ['error' => 'An account with that email already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Get role ID from selected role name
        $roleStmt = $this->pdo->prepare('SELECT id FROM campaign_department_roles WHERE LOWER(name) = :role_name LIMIT 1');
        $roleStmt->execute(['role_name' => $normalizedRoleName]);
        $role = $roleStmt->fetch();
        
        if (!$role) {
            http_response_code(422);
            return ['error' => 'Selected role does not exist in the system. Please contact administrator.'];
        }
        
        $roleId = (int) $role['id'];
        $barangayId = 1;

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_department_users (role_id, barangay_id, name, email, password_hash, is_active)
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

        $token = $this->generateToken($userId, $email, $roleId, $name);

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

            $stmt = $this->pdo->prepare('SELECT id, name, email, role_id, barangay_id FROM campaign_department_users WHERE id = :id AND is_active = 1 LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            $newToken = $this->generateToken((int) $user['id'], $user['email'], (int) $user['role_id'], $user['name'] ?? null);

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
        // CRITICAL PROOF: Log that this endpoint was called
        error_log('=== CRITICAL PROOF: AuthController::me() CALLED ===');
        error_log('CRITICAL: Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'NOT SET'));
        error_log('CRITICAL: User parameter: ' . ($user ? json_encode($user, JSON_PRETTY_PRINT) : 'NULL'));
        try {
            if (!$user) {
                error_log('CRITICAL: User is NULL - returning 401');
                http_response_code(401);
                return ['error' => 'Unauthorized'];
            }

            // If PDO is null, return user data from JWT token (already validated by middleware)
            if ($this->pdo === null) {
                error_log('AuthController::me - PDO is null, returning user from JWT token');
                return ['user' => $this->publicUser($user)];
            }

            // Fetch full user data with barangay name and role name
            $userId = (int) $user['id'];
            $stmt = $this->pdo->prepare('
                SELECT u.id, u.name, u.email, u.role_id, u.barangay_id, u.phone_number, u.created_at, 
                       b.name as barangay_name, r.name as role_name
                FROM campaign_department_users u 
                LEFT JOIN `campaign_department_barangays` b ON b.id = u.barangay_id 
                LEFT JOIN `campaign_department_roles` r ON r.id = u.role_id
                WHERE u.id = :id AND u.is_active = 1
            ');
            $stmt->execute(['id' => $userId]);
            $fullUser = $stmt->fetch(PDO::FETCH_ASSOC);

            // TASK 2: PROVE REAL API RESPONSE - Log exact database result
            $dbResultJson = json_encode($fullUser, JSON_PRETTY_PRINT);
            error_log("=== TASK 2 PROOF: Database query result ===\n" . $dbResultJson);
            
            // TASK 2: Log middleware user data
            $middlewareUserJson = json_encode($user, JSON_PRETTY_PRINT);
            error_log("=== TASK 2 PROOF: Middleware user data ===\n" . $middlewareUserJson);

            if ($fullUser) {
                // Ensure name is set - use from database query, fallback to middleware user data if empty
                if (empty($fullUser['name']) && !empty($user['name'])) {
                    $fullUser['name'] = $user['name'];
                    error_log('TASK 2 PROOF: Name was empty in DB, using middleware name: ' . $user['name']);
                }
                $publicUserData = $this->publicUser($fullUser);
                $finalResponse = ['user' => $publicUserData];
                $finalResponseJson = json_encode($finalResponse, JSON_PRETTY_PRINT);
                error_log("=== TASK 2 PROOF: Final API Response ===\n" . $finalResponseJson);
                return $finalResponse;
            }

            // If query returned no results, use middleware user data (which came from database)
            error_log('TASK 2 PROOF: Database query returned no results, using middleware user');
            $publicUserData = $this->publicUser($user);
            $finalResponse = ['user' => $publicUserData];
            $finalResponseJson = json_encode($finalResponse, JSON_PRETTY_PRINT);
            error_log("=== TASK 2 PROOF: Final API Response (from middleware) ===\n" . $finalResponseJson);
            return $finalResponse;
        } catch (\PDOException $e) {
            error_log('AuthController::me database error: ' . $e->getMessage());
            // If database query fails, return user from JWT token as fallback
            return ['user' => $this->publicUser($user)];
        } catch (\Throwable $e) {
            error_log('AuthController::me error: ' . $e->getMessage());
            error_log('AuthController::me stack: ' . $e->getTraceAsString());
            // Return user from JWT token as fallback
            return ['user' => $this->publicUser($user)];
        }
    }

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
        
        // Include name in token if provided (for fallback when database unavailable)
        if ($name !== null) {
            $payload['name'] = $name;
        }

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
        // TASK 3: PROVE WHERE "User" IS COMING FROM
        $nameValue = isset($user['name']) ? $user['name'] : 'NOT_SET';
        $nameType = isset($user['name']) ? gettype($user['name']) : 'N/A';
        $nameEmpty = isset($user['name']) && empty($user['name']) ? 'YES' : 'NO';
        $nameTrimmed = isset($user['name']) ? trim($user['name']) : '';
        $nameIsEmptyAfterTrim = ($nameTrimmed === '');
        
        error_log("=== TASK 3 PROOF: publicUser() called ===");
        error_log("TASK 3: user['name'] value: " . var_export($nameValue, true));
        error_log("TASK 3: user['name'] type: " . $nameType);
        error_log("TASK 3: user['name'] empty(): " . $nameEmpty);
        error_log("TASK 3: user['name'] after trim: " . var_export($nameTrimmed, true));
        error_log("TASK 3: nameIsEmptyAfterTrim: " . ($nameIsEmptyAfterTrim ? 'YES' : 'NO'));
        
        // Get name from user array - use actual database value, only fallback to 'User' if truly missing/empty
        $userName = isset($user['name']) && trim($user['name']) !== '' ? trim($user['name']) : 'User';
        error_log("TASK 3: Final userName assigned: " . var_export($userName, true));
        error_log("TASK 3: Fallback 'User' used: " . ($userName === 'User' ? 'YES' : 'NO'));
        
        $result = [
            'id' => (int) ($user['id'] ?? 0),
            'name' => $userName,
            'email' => $user['email'] ?? '',
            'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
            'role' => $user['role_name'] ?? $user['role'] ?? null, // Include role name from database
            'barangay_id' => isset($user['barangay_id']) ? (int) $user['barangay_id'] : null,
            'barangay_name' => $user['barangay_name'] ?? null,
            'phone_number' => $user['phone_number'] ?? $user['phone'] ?? null,
            'created_at' => $user['created_at'] ?? null,
        ];
        error_log("TASK 3: publicUser() returning: " . json_encode($result, JSON_PRETTY_PRINT));
        return $result;
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
            $stmt = $this->pdo->prepare('SELECT id FROM campaign_department_users WHERE email = :email AND id != :id');
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

        $sql = 'UPDATE campaign_department_users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params_array);

        // Fetch updated user
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_users WHERE id = :id');
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
        $stmt = $this->pdo->prepare('SELECT password FROM campaign_department_users WHERE id = :id');
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
        $stmt = $this->pdo->prepare('UPDATE campaign_department_users SET password = :password, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);

        return ['message' => 'Password changed successfully'];
    }

    /**
     * Best-effort automatic repair for the default admin account.
     * This avoids "Invalid credentials" when the DB seed is out of sync.
     */
    private function maybeRepairAdminUser(string $email, string $password): ?array
    {
        // If PDO is null (database unavailable), return null - demo login already handled in login()
        if ($this->pdo === null) {
            return null;
        }
        
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

        try {
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
            CREATE TABLE IF NOT EXISTS campaign_department_users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role_id INT UNSIGNED NOT NULL,
                barangay_id INT UNSIGNED NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_campaign_department_users_role FOREIGN KEY (role_id) REFERENCES `campaign_department_roles`(id),
                CONSTRAINT fk_campaign_department_users_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id)
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
        $checkStmt = $this->pdo->prepare('SELECT id FROM campaign_department_users WHERE email = :email OR id = 1 LIMIT 1');
        $checkStmt->execute(['email' => $email]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Update existing admin user with the new password
            $update = $this->pdo->prepare("
                UPDATE campaign_department_users
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
                INSERT INTO campaign_department_users (id, role_id, barangay_id, name, email, password_hash, is_active)
                VALUES (1, 1, 1, 'Admin User', :email, :password_hash, 1)
            ");
            $insert->execute([
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
        }

            // Reload the user record
            $reload = $this->pdo->prepare('SELECT id, name, email, password_hash, role_id, barangay_id FROM campaign_department_users WHERE email = :email AND is_active = 1 LIMIT 1');
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
        } catch (\PDOException $e) {
            error_log('Database error in maybeRepairAdminUser: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initiate Google OAuth login - redirects to Google
     */
    public function google(?array $user = null, array $params = []): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get Google OAuth credentials from environment
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['GOOGLE_SECRET'] ?? '';
        
        if (empty($clientId) || empty($clientSecret)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Google OAuth not configured']);
            exit;
        }

        // Get the base URL for redirect
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $redirectUri = $protocol . '://' . $host . $scriptPath . '/api/v1/auth/google/callback';
        
        // Generate state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        
        // Build Google OAuth URL
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account'
        ]);
        
        // Redirect to Google
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(?array $user = null, array $params = []): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get Google OAuth credentials
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['GOOGLE_SECRET'] ?? '';
        
        if (empty($clientId) || empty($clientSecret)) {
            $this->redirectWithError('Google OAuth not configured');
            return;
        }

        // Verify state (CSRF protection)
        $state = $_GET['state'] ?? '';
        $sessionState = $_SESSION['google_oauth_state'] ?? '';
        
        if (empty($state) || $state !== $sessionState) {
            $this->redirectWithError('Invalid state parameter');
            return;
        }
        
        unset($_SESSION['google_oauth_state']);

        // Get authorization code
        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            $error = $_GET['error'] ?? 'Authorization failed';
            $this->redirectWithError($error);
            return;
        }

        // Exchange code for access token
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $redirectUri = $protocol . '://' . $host . $scriptPath . '/api/v1/auth/google/callback';
        
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $tokenData = [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        
        $tokenResponse = curl_exec($ch);
        $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($tokenHttpCode !== 200) {
            $this->redirectWithError('Failed to exchange code for token');
            return;
        }

        $tokenData = json_decode($tokenResponse, true);
        if (!isset($tokenData['access_token'])) {
            $this->redirectWithError('Invalid token response');
            return;
        }

        // Get user info from Google
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($tokenData['access_token']);
        
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userInfoResponse = curl_exec($ch);
        $userInfoHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($userInfoHttpCode !== 200) {
            $this->redirectWithError('Failed to get user info from Google');
            return;
        }

        $userInfo = json_decode($userInfoResponse, true);
        if (!isset($userInfo['email'])) {
            $this->redirectWithError('Email not provided by Google');
            return;
        }

        // Find or create user
        $email = strtolower(trim($userInfo['email']));
        $name = $userInfo['name'] ?? $userInfo['given_name'] ?? 'Google User';
        $googleId = $userInfo['id'] ?? null;

        try {
            // Check if user exists
            $stmt = $this->pdo->prepare('SELECT id, name, email, role_id, barangay_id FROM campaign_department_users WHERE LOWER(TRIM(email)) = :email AND is_active = 1 LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Create new user
                // Get default role (assuming role_id 2 is for regular campaign_department_users, adjust as needed)
                $defaultRoleId = 2;
                $defaultBarangayId = 1;

                $insert = $this->pdo->prepare("
                    INSERT INTO campaign_department_users (name, email, role_id, barangay_id, is_active, created_at)
                    VALUES (:name, :email, :role_id, :barangay_id, 1, NOW())
                ");
                $insert->execute([
                    'name' => $name,
                    'email' => $email,
                    'role_id' => $defaultRoleId,
                    'barangay_id' => $defaultBarangayId
                ]);

                $userId = (int) $this->pdo->lastInsertId();
                $user = [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role_id' => $defaultRoleId,
                    'barangay_id' => $defaultBarangayId
                ];
            }
        } catch (\PDOException $e) {
            error_log('Database error in Google callback: ' . $e->getMessage());
            $this->redirectWithError('Database connection failed. Please try again later.');
            return;
        } catch (\Throwable $e) {
            error_log('Error in Google callback: ' . $e->getMessage());
            $this->redirectWithError('An error occurred during authentication. Please try again.');
            return;
        }

        // Generate JWT token
        $token = $this->generateToken((int) $user['id'], $user['email'], (int) $user['role_id'], $user['name'] ?? null);

        // Redirect to dashboard with token in URL (will be stored in localStorage by JavaScript)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $redirectUrl = $protocol . '://' . $host . $scriptPath . '/public/dashboard.php?google_login=1&token=' . urlencode($token);
        
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Helper to redirect with error message
     */
    private function redirectWithError(string $error): void
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $redirectUrl = $protocol . '://' . $host . $scriptPath . '/index.php?error=' . urlencode($error);
        
        header('Location: ' . $redirectUrl);
        exit;
    }
}

