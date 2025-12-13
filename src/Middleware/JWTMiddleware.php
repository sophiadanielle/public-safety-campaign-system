<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use RuntimeException;

class JWTMiddleware
{
    /**
     * Validate JWT from the Authorization header and return the user record.
     *
     * @throws RuntimeException when token is invalid or user not found.
     */
    public static function authenticate(PDO $pdo, string $jwtSecret, string $expectedAudience, string $expectedIssuer): array
    {
        $token = self::getBearerToken();
        if (!$token) {
            throw new RuntimeException('Authorization token missing');
        }

        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

        if (($decoded->aud ?? null) !== $expectedAudience || ($decoded->iss ?? null) !== $expectedIssuer) {
            throw new RuntimeException('Token audience/issuer mismatch');
        }

        $userId = (int) ($decoded->sub ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Invalid token subject');
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role_id, barangay_id FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        return $user;
    }

    private static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
        return null;
    }
}


