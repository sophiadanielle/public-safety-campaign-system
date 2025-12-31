<?php

declare(strict_types=1);

use App\Controllers\IntegrationController;
use App\Middleware\JWTMiddleware;

return [
    // Legacy log endpoint
    [
        'method' => 'POST',
        'path' => '/api/v1/integrations/log',
        'handler' => [IntegrationController::class, 'log'],
    ],
    
    // List all external systems
    [
        'method' => 'GET',
        'path' => '/api/v1/integrations/systems',
        'handler' => [IntegrationController::class, 'listSystems'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Get available systems for a module
    [
        'method' => 'GET',
        'path' => '/api/v1/integrations/modules/{module}/systems',
        'handler' => [IntegrationController::class, 'getModuleSystems'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Query external database
    [
        'method' => 'POST',
        'path' => '/api/v1/integrations/query/database',
        'handler' => [IntegrationController::class, 'queryDatabase'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Query external API
    [
        'method' => 'POST',
        'path' => '/api/v1/integrations/query/api',
        'handler' => [IntegrationController::class, 'queryApi'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Get cached data
    [
        'method' => 'GET',
        'path' => '/api/v1/integrations/cache',
        'handler' => [IntegrationController::class, 'getCachedData'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Sync data from external system
    [
        'method' => 'POST',
        'path' => '/api/v1/integrations/sync',
        'handler' => [IntegrationController::class, 'syncData'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Get integration query logs
    [
        'method' => 'GET',
        'path' => '/api/v1/integrations/logs',
        'handler' => [IntegrationController::class, 'getLogs'],
        'middleware' => JWTMiddleware::class,
    ],
];





