<?php

declare(strict_types=1);

use App\Controllers\SurveyController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys',
        'handler' => [SurveyController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/surveys',
        'handler' => [SurveyController::class, 'store'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/surveys/{id}/questions',
        'handler' => [SurveyController::class, 'addQuestion'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/surveys/{id}/publish',
        'handler' => [SurveyController::class, 'publish'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys/{id}',
        'handler' => [SurveyController::class, 'show'],
        // public for published surveys
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/surveys/{id}/responses',
        'handler' => [SurveyController::class, 'submitResponse'],
        // public for published surveys
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys/{id}/responses/export',
        'handler' => [SurveyController::class, 'exportCsv'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys/{id}/qr',
        'handler' => [SurveyController::class, 'qrLink'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/surveys/{id}/close',
        'handler' => [SurveyController::class, 'closeSurvey'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys/{id}/responses',
        'handler' => [SurveyController::class, 'getResponses'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys/{id}/results',
        'handler' => [SurveyController::class, 'aggregatedResults'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/surveys/{id}/results/export',
        'handler' => [SurveyController::class, 'exportAggregatedCsv'],
        'middleware' => JWTMiddleware::class,
    ],
];


