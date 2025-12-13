<?php

declare(strict_types=1);

use App\Controllers\SegmentController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/segments',
        'handler' => [SegmentController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/segments',
        'handler' => [SegmentController::class, 'store'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/segments/{id}/members/batch',
        'handler' => [SegmentController::class, 'importMembers'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/segments/{id}/evaluate',
        'handler' => [SegmentController::class, 'evaluate'],
        'middleware' => JWTMiddleware::class,
    ],
];


