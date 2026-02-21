<?php
/**
 * User Management Routes
 * Handles CRUD operations for users
 */

declare(strict_types=1);

use App\Controllers\UserManagementController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/users/manage',
        'handler' => [UserManagementController::class, 'listUsers'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/users/manage/{id}',
        'handler' => [UserManagementController::class, 'getUser'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/users/manage',
        'handler' => [UserManagementController::class, 'createUser'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/users/manage/{id}',
        'handler' => [UserManagementController::class, 'updateUser'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/users/manage/{id}/archive',
        'handler' => [UserManagementController::class, 'archiveUser'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/users/manage/{id}/restore',
        'handler' => [UserManagementController::class, 'restoreUser'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/users/manage/{id}/avatar',
        'handler' => [UserManagementController::class, 'uploadAvatar'],
        'middleware' => JWTMiddleware::class,
    ],
];
