<?php

declare(strict_types=1);

use App\Controllers\ImpactController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/campaigns/{id}/impact',
        'handler' => [ImpactController::class, 'metrics'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/reports/generate/{campaign_id}',
        'handler' => [ImpactController::class, 'generateReport'],
        'middleware' => JWTMiddleware::class,
    ],
];





