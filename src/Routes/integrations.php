<?php

declare(strict_types=1);

use App\Controllers\IntegrationController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'POST',
        'path' => '/api/v1/integrations/log',
        'handler' => [IntegrationController::class, 'log'],
        // can be public; add JWT if desired
    ],
];





