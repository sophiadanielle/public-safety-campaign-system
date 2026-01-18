<?php
/**
 * Get current user role from cookie (set by JavaScript) or JWT header
 * This is used by PHP pages to determine role for server-side rendering
 * 
 * ROOT CAUSE FIX: PHP pages don't receive JWT in HTTP headers (JWT is in localStorage).
 * Solution: JavaScript decodes JWT, extracts role_id, sets cookie, PHP reads cookie.
 */
function getCurrentUserRole(): ?string {
    // METHOD 1: Try to get role_id from cookie (set by JavaScript from JWT)
    // Cookie is set by JavaScript after login: document.cookie = "user_role_id=123"
    $roleIdFromCookie = $_COOKIE['user_role_id'] ?? null;
    
    if ($roleIdFromCookie && is_numeric($roleIdFromCookie)) {
        try {
            require_once __DIR__ . '/../../src/Config/db_connect.php';
            
            if (isset($pdo) && $pdo instanceof PDO) {
                $stmt = $pdo->prepare('SELECT r.name FROM campaign_department_roles r WHERE r.id = :role_id LIMIT 1');
                $stmt->execute(['role_id' => (int)$roleIdFromCookie]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $roleName = strtolower(trim($result['name']));
                    $roleMappings = [
                        'partner' => 'viewer',
                        'partner representative' => 'viewer',
                        'partner_representative' => 'viewer',
                        'viewer' => 'viewer',
                        'barangay administrator' => 'admin',
                        'barangay admin' => 'admin',
                        'barangay staff' => 'staff',
                    ];
                    $mappedRole = $roleMappings[$roleName] ?? $roleName;
                    // Also check if role name contains 'partner' or 'viewer'
                    if (strpos($roleName, 'partner') !== false || strpos($roleName, 'viewer') !== false) {
                        $mappedRole = 'viewer';
                    }
                    error_log('RBAC get_user_role: role_id=' . $roleIdFromCookie . ', db_name=' . $result['name'] . ', mapped=' . $mappedRole);
                    return $mappedRole;
                }
            }
        } catch (Exception $e) {
            error_log('getCurrentUserRole() cookie lookup error: ' . $e->getMessage());
        }
    }
    
    // METHOD 2: Try to get JWT from Authorization header (for API routes)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        
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
                        $roleMappings = [
                            'partner' => 'viewer',
                            'barangay administrator' => 'admin',
                            'barangay admin' => 'admin',
                            'barangay staff' => 'staff',
                        ];
                        return $roleMappings[$roleName] ?? $roleName;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('getCurrentUserRole() JWT decode error: ' . $e->getMessage());
        }
    }
    
    // DEBUG: Log when role cannot be determined
    error_log('RBAC DEBUG: getCurrentUserRole() returning null - no cookie or header found. Cookies: ' . json_encode($_COOKIE));
    
    return null;
}

