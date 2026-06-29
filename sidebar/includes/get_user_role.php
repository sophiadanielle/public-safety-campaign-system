<?php
/**
 * Lightweight page role detection for server-rendered navigation.
 *
 * API permissions still enforce real access. This helper is only for page/UI rendering,
 * so it avoids database work and noisy production logs that can break FastCGI headers.
 */
function getCurrentUserRole(): ?string
{
    $roleId = getRoleIdFromCookie();

    if ($roleId === null) {
        $roleId = getRoleIdFromJwtCookie();
    }

    if ($roleId === null) {
        return null;
    }

    return mapRoleIdToPageRole($roleId);
}

function getRoleIdFromCookie(): ?int
{
    $roleId = $_COOKIE['user_role_id'] ?? null;

    if ($roleId !== null && is_numeric($roleId)) {
        return (int) $roleId;
    }

    return null;
}

function getRoleIdFromJwtCookie(): ?int
{
    $token = $_COOKIE['jwt_token'] ?? null;

    if (!$token) {
        return null;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'), true);
    if ($payloadJson === false) {
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }

    $roleId = $payload['role_id'] ?? $payload['rid'] ?? null;
    if (!is_numeric($roleId)) {
        return null;
    }

    $roleId = (int) $roleId;
    if (!headers_sent()) {
        setcookie('user_role_id', (string) $roleId, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        $_COOKIE['user_role_id'] = (string) $roleId;
    }

    return $roleId;
}

function mapRoleIdToPageRole(int $roleId): string
{
    return $roleId === 1 ? 'admin' : 'viewer';
}
