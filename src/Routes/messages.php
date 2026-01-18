<?php

declare(strict_types=1);

use App\Controllers\MessageController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/messages/conversations',
        'handler' => [MessageController::class, 'getConversations'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/messages/conversations/{id}',
        'handler' => [MessageController::class, 'getMessages'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'POST',
        'path' => '/api/v1/messages/send',
        'handler' => [MessageController::class, 'sendMessage'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'PUT',
        'path' => '/api/v1/messages/conversations/{id}/read',
        'handler' => [MessageController::class, 'markRead'],
        'middleware' => JWTMiddleware::class,
    ],
];







