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
    public static function authenticate(?PDO $pdo, string $jwtSecret, string $expectedAudience, string $expectedIssuer): array
    {
        // TASK 4: PROVE TOKEN PRESENCE ON BACKEND
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        error_log("=== TASK 4 PROOF: JWTMiddleware::authenticate called ===");
        error_log("TASK 4: HTTP_AUTHORIZATION header present: " . (isset($_SERVER['HTTP_AUTHORIZATION']) ? 'YES' : 'NO'));
        error_log("TASK 4: HTTP_AUTHORIZATION value (first 50 chars): " . substr($authHeader, 0, 50));
        error_log("TASK 4: All headers with 'auth' in name:");
        foreach ($_SERVER as $key => $value) {
            if (stripos($key, 'auth') !== false || stripos($key, 'authorization') !== false) {
                error_log("  $key: " . substr((string)$value, 0, 50));
            }
        }
        
        $token = self::getBearerToken();
        error_log("TASK 4: getBearerToken() returned: " . ($token ? 'YES (length: ' . strlen($token) . ', first 30: ' . substr($token, 0, 30) . '...)' : 'NULL/EMPTY'));
        if (!$token) {
            error_log("=== TASK 4 PROOF: No token found, throwing exception ===");
            throw new RuntimeException('Authorization token missing');
        }

        error_log("TASK 4: Attempting to decode token...");
        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
        error_log("TASK 4: Token decoded successfully, user ID: " . ($decoded->sub ?? 'N/A'));

        if (($decoded->aud ?? null) !== $expectedAudience || ($decoded->iss ?? null) !== $expectedIssuer) {
            throw new RuntimeException('Token audience/issuer mismatch');
        }

        $userId = (int) ($decoded->sub ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Invalid token subject');
        }

        // If PDO is null, return user data from JWT token only (name/email from token claims)
        if ($pdo === null) {
            return [
                'id' => $userId,
                'name' => $decoded->name ?? 'User',
                'email' => $decoded->email ?? '',
                'role_id' => (int) ($decoded->role_id ?? 0),
                'barangay_id' => null,
                'is_active' => 1,
            ];
        }

        // Query database for full user record
        error_log('DIAGNOSTIC: JWTMiddleware::authenticate - Querying database for user ID: ' . $userId);
        try {
            $stmt = $pdo->prepare('SELECT id, name, email, role_id, barangay_id FROM campaign_department_users WHERE id = :id AND is_active = 1 LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // CRITICAL PROOF: Log exact database query result
            error_log('=== CRITICAL PROOF: JWTMiddleware database query result ===');
            error_log('CRITICAL: Query executed: SELECT id, name, email, role_id, barangay_id FROM campaign_department_users WHERE id = ' . $userId . ' AND is_active = 1');
            error_log('CRITICAL: fetch() returned: ' . ($user === false ? 'FALSE (no rows)' : ($user === null ? 'NULL' : 'ARRAY')));
            if ($user !== false && $user !== null) {
                error_log('CRITICAL: User array: ' . json_encode($user, JSON_PRETTY_PRINT));
                error_log('CRITICAL: user["name"] value: ' . (isset($user['name']) ? var_export($user['name'], true) : 'KEY NOT SET'));
                error_log('CRITICAL: user["name"] type: ' . (isset($user['name']) ? gettype($user['name']) : 'N/A'));
                error_log('CRITICAL: user["name"] empty check: ' . (isset($user['name']) && empty($user['name']) ? 'EMPTY' : 'NOT EMPTY'));
            } else {
                error_log('CRITICAL: User NOT FOUND in database - query returned no rows');
            }
        } catch (\PDOException $e) {
            error_log('CRITICAL ERROR: Database query failed: ' . $e->getMessage());
            error_log('CRITICAL ERROR: SQL State: ' . $e->getCode());
            $user = false;
        }
        
        if (!$user) {
            // If user not found in database, return data from JWT token as fallback
            error_log('DIAGNOSTIC: JWTMiddleware::authenticate - User not found in DB, using JWT token data');
            $fallbackUser = [
                'id' => $userId,
                'name' => $decoded->name ?? 'User',
                'email' => $decoded->email ?? '',
                'role_id' => (int) ($decoded->role_id ?? 0),
                'barangay_id' => null,
                'is_active' => 1,
            ];
            error_log('DIAGNOSTIC: JWTMiddleware::authenticate - Returning fallback user: ' . json_encode($fallbackUser));
            return $fallbackUser;
        }

        error_log('DIAGNOSTIC: JWTMiddleware::authenticate - Returning database user: ' . json_encode($user));
        return $user;
    }

    private static function getBearerToken(): ?string
    {
        // Try multiple methods to get Authorization header (different server configurations)
        $header = '';
        
        // TASK 4: PROVE TOKEN EXTRACTION
        error_log("=== TASK 4 PROOF: getBearerToken() called ===");
        
        // Method 1: Standard HTTP_AUTHORIZATION (works with most servers)
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
            error_log("TASK 4: Found via HTTP_AUTHORIZATION: " . substr($header, 0, 50));
        }
        // Method 2: REDIRECT_HTTP_AUTHORIZATION (when using mod_rewrite)
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            error_log("TASK 4: Found via REDIRECT_HTTP_AUTHORIZATION: " . substr($header, 0, 50));
        }
        // Method 3: Use getallheaders() function (Apache)
        elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
                error_log("TASK 4: Found via getallheaders()['Authorization']: " . substr($header, 0, 50));
            } elseif (isset($headers['authorization'])) {
                $header = $headers['authorization'];
                error_log("TASK 4: Found via getallheaders()['authorization']: " . substr($header, 0, 50));
            } else {
                error_log("TASK 4: getallheaders() exists but no Authorization header");
            }
        }
        // Method 4: Use apache_request_headers() (Apache)
        elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $header = $headers['Authorization'];
                error_log("TASK 4: Found via apache_request_headers()['Authorization']: " . substr($header, 0, 50));
            } elseif (isset($headers['authorization'])) {
                $header = $headers['authorization'];
                error_log("TASK 4: Found via apache_request_headers()['authorization']: " . substr($header, 0, 50));
            } else {
                error_log("TASK 4: apache_request_headers() exists but no Authorization header");
            }
        } else {
            error_log("TASK 4: No header found via any method");
        }
        
        // Extract Bearer token
        if ($header && stripos($header, 'Bearer ') === 0) {
            $token = trim(substr($header, 7));
            error_log("TASK 4: Extracted token (length: " . strlen($token) . ", first 30: " . substr($token, 0, 30) . "...)");
            return $token;
        }
        
        error_log("=== TASK 4 PROOF: No Bearer token, returning null ===");
        return null;
    }
}


