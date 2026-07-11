<?php

declare(strict_types=1);

use App\Controllers\AiRecommendationPlanningController;
use App\Middleware\JWTMiddleware;
use App\Middleware\ViewerBlockMiddleware;

return [
    [
        'method' => 'POST',
        'path' => '/api/v1/ai-recommendations/generate',
        'handler' => [AiRecommendationPlanningController::class, 'generate'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/ai-recommendations/recalculate',
        'handler' => [AiRecommendationPlanningController::class, 'recalculate'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/ai-recommendations/backfill-missing',
        'handler' => [AiRecommendationPlanningController::class, 'backfillMissing'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/ai-recommendations/approve-budget',
        'handler' => [AiRecommendationPlanningController::class, 'approveBudget'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/ai-recommendations/approve-dates',
        'handler' => [AiRecommendationPlanningController::class, 'approveDates'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/ai-recommendations/reject',
        'handler' => [AiRecommendationPlanningController::class, 'reject'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
];
