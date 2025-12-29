<?php

declare(strict_types=1);

use App\Controllers\AutoMLController;
use App\Middleware\JWTMiddleware;

return [
    // Existing prediction endpoint
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/predict',
        'handler' => [AutoMLController::class, 'predict'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Enhanced prediction endpoints
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/predict/conflict',
        'handler' => [AutoMLController::class, 'predictConflict'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/predict/engagement',
        'handler' => [AutoMLController::class, 'predictEngagement'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/predict/readiness',
        'handler' => [AutoMLController::class, 'forecastReadiness'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Training endpoints (Admin only - enforced in controller)
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/training/start',
        'handler' => [AutoMLController::class, 'startTraining'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/automl/training/status/{id}',
        'handler' => [AutoMLController::class, 'checkTrainingStatus'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/training/deploy/{id}',
        'handler' => [AutoMLController::class, 'deployModel'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/automl/training/models',
        'handler' => [AutoMLController::class, 'listModels'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/automl/training/data-preview',
        'handler' => [AutoMLController::class, 'getDataPreview'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // AI Insights for dashboard
    [
        'method' => 'GET',
        'path' => '/api/v1/automl/insights',
        'handler' => [AutoMLController::class, 'getInsights'],
        'middleware' => JWTMiddleware::class,
    ],
];





