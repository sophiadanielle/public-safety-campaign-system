<?php
/**
 * Audit Log Routes
 */

use App\Controllers\AuditController;

return [
    // Get all audit logs (consolidated from all sources)
    [
        'method' => 'GET',
        'path' => '/api/v1/audit-logs',
        'handler' => [AuditController::class, 'index'],
        'auth' => true,
    ],
];
