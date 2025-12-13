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
        $email = isset($input['email']) ? filter_var($input['email'], FILTER_VALIDATE_EMAIL) : null;
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            http_response_code(422);
            return ['error' => 'Email and password are required.'];
        }

        $stmt = $this->pdo->prepare('SELECT id, name, email, password_hash, role_id, barangay_id FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            return ['error' => 'Invalid credentials'];
        }

        $token = $this->generateToken((int) $user['id'], $user['email'], (int) $user['role_id']);

        return [
            'token' => $token,
            'expires_in' => $this->jwtExpirySeconds,
            'user' => $this->publicUser($user),
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
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        return ['user' => $this->publicUser($user)];
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
        ];
    }
}

