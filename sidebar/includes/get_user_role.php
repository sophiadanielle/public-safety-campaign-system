<?php
/**
 * Get current user role from cookie (set by JavaScript) or JWT header
 * This is used by PHP pages to determine role for server-side rendering
 * 
 * ROOT CAUSE FIX: PHP pages don't receive JWT in HTTP headers (JWT is in localStorage).
 * Solution: JavaScript decodes JWT, extracts role_id, sets cookie, PHP reads cookie.
 */
function getCurrentUserRole(): ?string {
    // METHOD 1: Try to get role_id from cookie (set server-side during login OR by JavaScript from JWT)
    // IMPORTANT: Cookie must be set with path=/ to be available on all pages
    // Server-side cookie (set during login) takes priority over JavaScript-set cookie
    $roleIdFromCookie = $_COOKIE['user_role_id'] ?? null;
    
    // Debug logging to help diagnose cookie issues
    if (!$roleIdFromCookie) {
        $availableCookies = array_keys($_COOKIE);
        error_log('RBAC DEBUG: No user_role_id cookie found. Available cookies: ' . json_encode($availableCookies) . 
                  ', Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    }
    
    if ($roleIdFromCookie && is_numeric($roleIdFromCookie)) {
        try {
            require_once __DIR__ . '/../../src/Config/db_connect.php';
            
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare('SELECT r.name FROM campaign_department_roles r WHERE r.id = :role_id LIMIT 1');
                $stmt->execute(['role_id' => (int)$roleIdFromCookie]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $roleName = strtolower(trim($result['name']));
                    // LGU Governance Roles Mapping (Defense-Approved)
                    $roleMappings = [
                        // External partners map to viewer (read-only)
                        'partner' => 'viewer',
                        'partner representative' => 'viewer',
                        'partner_representative' => 'viewer',
                        'viewer' => 'viewer',
                        // LGU Governance Chain
                        'staff' => 'staff',
                        'secretary' => 'secretary',
                        'kagawad' => 'kagawad',
                        'captain' => 'captain',
                        // Technical admin
                        'admin' => 'admin',
                        // Legacy role mappings (backward compatibility)
                        'barangay administrator' => 'admin',
                        'barangay admin' => 'admin',
                        'barangay staff' => 'staff',
                        'system_admin' => 'admin',
                        'barangay_admin' => 'admin',
                    ];
                    $mappedRole = $roleMappings[$roleName] ?? $roleName;
                    // Also check if role name contains 'partner' or 'viewer' (fallback)
                    if (strpos($roleName, 'partner') !== false || (strpos($roleName, 'viewer') !== false && !isset($roleMappings[$roleName]))) {
                        $mappedRole = 'viewer';
                    }
                    error_log('RBAC get_user_role: role_id=' . $roleIdFromCookie . ', db_name=' . $result['name'] . ', mapped=' . $mappedRole);
                    // Store in session for persistence across page loads (only if headers not sent)
                    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        $_SESSION['user_role'] = $mappedRole;
                    }
                    return $mappedRole;
                }
            }
        } catch (Exception $e) {
            error_log('getCurrentUserRole() cookie lookup error: ' . $e->getMessage());
        }
    }
    
    // METHOD 2: Try to get JWT from cookie (set during login)
    $token = $_COOKIE['jwt_token'] ?? null;
    
    // METHOD 3: Try to get JWT from Authorization header (for API routes)
    if (!$token) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    // CRITICAL FALLBACK: If role cookie is missing, decode JWT to get role
    if ($token) {
        error_log('RBAC get_user_role: Role cookie missing, attempting JWT fallback. JWT token: ' . (strlen($token) > 0 ? 'PRESENT (length: ' . strlen($token) . ')' : 'EMPTY'));
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            $envPath = __DIR__ . '/../../.env';
            $jwtSecret = 'your-secret-key-change-in-production';
            $jwtIssuer = 'public-safety-campaign-system';
            $jwtAudience = 'public-safety-campaign-system';
            
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) continue;
                    if (strpos($line, '=') === false) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($name === 'JWT_SECRET') $jwtSecret = $value;
                    if ($name === 'JWT_ISSUER') $jwtIssuer = $value;
                    if ($name === 'JWT_AUDIENCE') $jwtAudience = $value;
                }
            }
            
            $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtSecret, 'HS256'));
            
            if (($decoded->aud ?? null) !== $jwtAudience || ($decoded->iss ?? null) !== $jwtIssuer) {
                return null;
            }
            
            $roleId = (int) ($decoded->role_id ?? 0);
            if ($roleId > 0) {
                require_once __DIR__ . '/../../src/Config/db_connect.php';
                
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare('SELECT r.name FROM campaign_department_roles r WHERE r.id = :role_id LIMIT 1');
                    $stmt->execute(['role_id' => $roleId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        $roleName = strtolower(trim($result['name']));
                        // LGU Governance Roles Mapping (Defense-Approved)
                        $roleMappings = [
                            // External partners map to viewer (read-only)
                            'partner' => 'viewer',
                            'partner representative' => 'viewer',
                            'partner_representative' => 'viewer',
                            'viewer' => 'viewer',
                            // LGU Governance Chain
                            'staff' => 'staff',
                            'secretary' => 'secretary',
                            'kagawad' => 'kagawad',
                            'captain' => 'captain',
                            // Technical admin
                            'admin' => 'admin',
                            // Legacy role mappings (backward compatibility)
                            'barangay administrator' => 'admin',
                            'barangay admin' => 'admin',
                            'barangay staff' => 'staff',
                            'system_admin' => 'admin',
                            'barangay_admin' => 'admin',
                        ];
                        $mappedRole = $roleMappings[$roleName] ?? $roleName;
                        // Also check if role name contains 'partner' or 'viewer' (fallback)
                        if (strpos($roleName, 'partner') !== false || (strpos($roleName, 'viewer') !== false && !isset($roleMappings[$roleName]))) {
                            $mappedRole = 'viewer';
                        }
                        error_log('RBAC get_user_role: JWT fallback SUCCESS - role_id=' . $roleId . ', db_name=' . $result['name'] . ', mapped=' . $mappedRole);
                        // Store in session for persistence across page loads (only if headers not sent)
                        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            $_SESSION['user_role'] = $mappedRole;
                        }
                        // Also set the role cookie for future requests (if headers not sent)
                        if (!headers_sent()) {
                            setcookie('user_role_id', (string)$roleId, [
                                'expires' => time() + (30 * 24 * 60 * 60),
                                'path' => '/',
                                'samesite' => 'Lax'
                            ]);
                            $_COOKIE['user_role_id'] = (string)$roleId;
                        }
                        return $mappedRole;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('getCurrentUserRole() JWT decode error: ' . $e->getMessage());
        }
    }
    
    // DEBUG: Log when role cannot be determined
    $availableCookies = array_keys($_COOKIE);
    $hasRoleCookie = isset($_COOKIE['user_role_id']);
    $hasJwtCookie = isset($_COOKIE['jwt_token']);
    error_log('RBAC DEBUG: getCurrentUserRole() returning null - no role detected. ' .
              'user_role_id cookie: ' . ($hasRoleCookie ? 'SET (' . $_COOKIE['user_role_id'] . ')' : 'NOT SET') .
              ', jwt_token cookie: ' . ($hasJwtCookie ? 'SET' : 'NOT SET') .
              ', All cookies: ' . implode(', ', $availableCookies));
    
    return null;
}

