<?php

declare(strict_types=1);

use App\Controllers\NotificationController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/notifications',
        'handler' => [NotificationController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/notifications/{id}/read',
        'handler' => [NotificationController::class, 'markRead'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/notifications/read-all',
        'handler' => [NotificationController::class, 'markAllRead'],
        'middleware' => JWTMiddleware::class,
    ],
];









