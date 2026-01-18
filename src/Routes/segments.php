<?php

declare(strict_types=1);

use App\Controllers\SegmentController;
use App\Middleware\JWTMiddleware;
use App\Middleware\ViewerBlockMiddleware;

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
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/segments/{id}',
        'handler' => [SegmentController::class, 'show'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/segments/{id}',
        'handler' => [SegmentController::class, 'update'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/segments/{id}/members',
        'handler' => [SegmentController::class, 'getMembers'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/segments/{id}/participation-history',
        'handler' => [SegmentController::class, 'getParticipationHistory'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/segments/{id}/link-campaign',
        'handler' => [SegmentController::class, 'linkToCampaign'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/segments/{id}/campaigns',
        'handler' => [SegmentController::class, 'getLinkedCampaigns'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/segments/{id}/members/batch',
        'handler' => [SegmentController::class, 'importMembers'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
];
