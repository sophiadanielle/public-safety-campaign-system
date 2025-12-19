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
];


