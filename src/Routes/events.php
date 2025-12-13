<?php

declare(strict_types=1);

use App\Controllers\EventController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/events',
        'handler' => [EventController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/events',
        'handler' => [EventController::class, 'store'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/events/{id}/attendance',
        'handler' => [EventController::class, 'attendance'],
        // public endpoint for check-in (no JWT)
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/events/{id}/attendance/export',
        'handler' => [EventController::class, 'exportCsv'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/events/{id}/qr',
        'handler' => [EventController::class, 'qrLink'],
        'middleware' => JWTMiddleware::class,
    ],
];


