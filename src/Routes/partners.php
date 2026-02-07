<?php

declare(strict_types=1);

use App\Controllers\PartnerController;
use App\Middleware\JWTMiddleware;
use App\Middleware\ViewerBlockMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/partners',
        'handler' => [PartnerController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/partners',
        'handler' => [PartnerController::class, 'store'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/partners/{id}/engage',
        'handler' => [PartnerController::class, 'engage'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/partners/{id}/assignments',
        'handler' => [PartnerController::class, 'assignments'],
        // public so partner portal can fetch without JWT; adjust as needed
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/partners/{id}',
        'handler' => [PartnerController::class, 'show'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/partners/{id}',
        'handler' => [PartnerController::class, 'update'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'DELETE',
        'path' => '/api/v1/partners/{id}',
        'handler' => [PartnerController::class, 'destroy'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
];





