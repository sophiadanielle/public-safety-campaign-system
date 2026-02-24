<?php
/**
 * Search Routes
 */

use App\Controllers\SearchController;
use App\Middleware\JWTMiddleware;

return [
    // Global search across all entities
    [
        'method' => 'GET',
        'path' => '/api/v1/search',
        'handler' => [SearchController::class, 'search'],
        'middleware' => JWTMiddleware::class,
    ],
];
