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
        'method' => 'GET',
        'path' => '/api/v1/content/usage',
        'handler' => [ContentController::class, 'getUsage'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/content/{id}',
        'handler' => [ContentController::class, 'show'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/content/{id}',
        'handler' => [ContentController::class, 'update'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/content/{id}/approval',
        'handler' => [ContentController::class, 'updateApproval'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/content/{id}/attach-campaign',
        'handler' => [ContentController::class, 'attachToCampaign'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/content/{id}/campaigns',
        'handler' => [ContentController::class, 'getCampaigns'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/content/{id}/use',
        'handler' => [ContentController::class, 'useContent'],
        'middleware' => JWTMiddleware::class,
    ],
];


