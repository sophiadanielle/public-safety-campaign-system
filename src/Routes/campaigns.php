<?php

declare(strict_types=1);

use App\Controllers\CampaignController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/campaigns',
        'handler' => [CampaignController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/campaigns',
        'handler' => [CampaignController::class, 'store'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/campaigns/{id}',
        'handler' => [CampaignController::class, 'show'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/campaigns/{id}',
        'handler' => [CampaignController::class, 'update'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/campaigns/{id}/schedules',
        'handler' => [CampaignController::class, 'addSchedule'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/campaigns/{id}/schedules',
        'handler' => [CampaignController::class, 'listSchedules'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PATCH',
        'path' => '/api/v1/campaigns/{id}/schedules/{sid}/send',
        'handler' => [CampaignController::class, 'sendSchedule'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/campaigns/{id}/segments',
        'handler' => [CampaignController::class, 'listSegments'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/campaigns/{id}/segments',
        'handler' => [CampaignController::class, 'syncSegments'],
        'middleware' => JWTMiddleware::class,
    ],
];


