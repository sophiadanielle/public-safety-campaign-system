<?php

declare(strict_types=1);

use App\Controllers\ReferenceStaffController;
use App\Middleware\JWTMiddleware;
use App\Middleware\ViewerBlockMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/reference-staff',
        'handler' => [ReferenceStaffController::class, 'index'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/reference-staff/roles',
        'handler' => [ReferenceStaffController::class, 'roles'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/reference-staff',
        'handler' => [ReferenceStaffController::class, 'store'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
    [
        'method' => 'DELETE',
        'path' => '/api/v1/reference-staff/{id}',
        'handler' => [ReferenceStaffController::class, 'destroy'],
        'middleware' => [JWTMiddleware::class, ViewerBlockMiddleware::class],
    ],
];
