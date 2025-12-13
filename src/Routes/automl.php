<?php

declare(strict_types=1);

use App\Controllers\AutoMLController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'POST',
        'path' => '/api/v1/automl/predict',
        'handler' => [AutoMLController::class, 'predict'],
        'middleware' => JWTMiddleware::class,
    ],
];





