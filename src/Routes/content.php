<?php

declare(strict_types=1);

use App\Controllers\ContentController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/content',
        'handler' => [ContentController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/content',
        'handler' => [ContentController::class, 'store'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/content/{id}/use',
        'handler' => [ContentController::class, 'useContent'],
        'middleware' => JWTMiddleware::class,
    ],
];


