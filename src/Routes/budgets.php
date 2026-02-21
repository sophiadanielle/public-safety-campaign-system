<?php
/**
 * Campaign Budgets API Routes
 * Handles CRUD operations for campaign budget line items
 */

declare(strict_types=1);

use App\Controllers\BudgetController;
use App\Middleware\JWTMiddleware;
use App\Middleware\ViewerBlockMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/budgets',
        'handler' => [BudgetController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/budgets/{id}',
        'handler' => [BudgetController::class, 'show'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/budgets',
        'handler' => [BudgetController::class, 'store'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/budgets/{id}',
        'handler' => [BudgetController::class, 'update'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'DELETE',
        'path' => '/api/v1/budgets/{id}',
        'handler' => [BudgetController::class, 'destroy'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/campaigns/{id}/budgets',
        'handler' => [BudgetController::class, 'getByCampaign'],
        'middleware' => JWTMiddleware::class,
    ],
];
