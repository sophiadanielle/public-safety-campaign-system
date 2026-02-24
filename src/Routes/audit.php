<?php
/**
 * Audit Log Routes
 */

use App\Controllers\AuditController;
use App\Middleware\JWTMiddleware;

return [
    // Get all audit logs (consolidated from all sources)
    [
        'method' => 'GET',
        'path' => '/api/v1/audit-logs',
        'handler' => [AuditController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
];
