<?php

declare(strict_types=1);

use App\Controllers\PartnerController;
use App\Middleware\JWTMiddleware;

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
        'middleware' => JWTMiddleware::class,
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
];





