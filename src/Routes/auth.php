<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/login',
        'handler' => [AuthController::class, 'login'],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/register',
        'handler' => [AuthController::class, 'register'],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/refresh',
        'handler' => [AuthController::class, 'refresh'],
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/users/me',
        'handler' => [AuthController::class, 'me'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/users/me',
        'handler' => [AuthController::class, 'updateProfile'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/users/change-password',
        'handler' => [AuthController::class, 'changePassword'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/auth/google',
        'handler' => [AuthController::class, 'google'],
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/auth/google/callback',
        'handler' => [AuthController::class, 'googleCallback'],
    ],
];


