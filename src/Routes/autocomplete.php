<?php

declare(strict_types=1);

use App\Controllers\AutocompleteController;
use App\Middleware\JWTMiddleware;

return [
    [
        'method' => 'GET',
        'path' => '/api/v1/autocomplete/campaign-titles',
        'handler' => [AutocompleteController::class, 'campaignTitles'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/autocomplete/barangays',
        'handler' => [AutocompleteController::class, 'barangays'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/autocomplete/locations',
        'handler' => [AutocompleteController::class, 'locations'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/autocomplete/staff',
        'handler' => [AutocompleteController::class, 'staff'],
        'middleware' => JWTMiddleware::class,
    ],
    [
        'method' => 'GET',
        'path' => '/api/v1/autocomplete/materials',
        'handler' => [AutocompleteController::class, 'materials'],
        'middleware' => JWTMiddleware::class,
    ],
];






