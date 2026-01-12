<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Middleware\JWTMiddleware;

return [
    // Get dashboard summary
    [
        'method' => 'GET',
        'path' => '/api/v1/dashboard/summary',
        'handler' => [DashboardController::class, 'summary'],
        'middleware' => JWTMiddleware::class, // Optional auth for filtering
    ],
    
    // Global search
    [
        'method' => 'GET',
        'path' => '/api/v1/dashboard/search',
        'handler' => [DashboardController::class, 'search'],
        'middleware' => JWTMiddleware::class,
    ],
];




