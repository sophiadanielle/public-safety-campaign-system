<?php

declare(strict_types=1);

use App\Controllers\EventController;
use App\Middleware\JWTMiddleware;

return [
    // List events (with filters)
    [
        'method' => 'GET',
        'path' => '/api/v1/events',
        'handler' => [EventController::class, 'index'],
        'middleware' => JWTMiddleware::class, // Optional auth for filtering
    ],
    
    // Get single event details
    [
        'method' => 'GET',
        'path' => '/api/v1/events/{id}',
        'handler' => [EventController::class, 'show'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Create event
    [
        'method' => 'POST',
        'path' => '/api/v1/events',
        'handler' => [EventController::class, 'store'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Update event
    [
        'method' => 'PUT',
        'path' => '/api/v1/events/{id}',
        'handler' => [EventController::class, 'update'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Check-in attendee (public for QR, requires auth for manual)
    [
        'method' => 'POST',
        'path' => '/api/v1/events/{id}/attendance',
        'handler' => [EventController::class, 'attendance'],
        // No middleware - public endpoint for QR check-ins
    ],
    
    // Get attendance list
    [
        'method' => 'GET',
        'path' => '/api/v1/events/{id}/attendance',
        'handler' => [EventController::class, 'getAttendance'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Export attendance CSV
    [
        'method' => 'GET',
        'path' => '/api/v1/events/{id}/attendance/export',
        'handler' => [EventController::class, 'exportCsv'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Generate QR code link
    [
        'method' => 'GET',
        'path' => '/api/v1/events/{id}/qr',
        'handler' => [EventController::class, 'qrLink'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Calendar view
    [
        'method' => 'GET',
        'path' => '/api/v1/events/calendar',
        'handler' => [EventController::class, 'calendar'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Check for conflicts
    [
        'method' => 'GET',
        'path' => '/api/v1/events/check-conflicts',
        'handler' => [EventController::class, 'checkConflictsEndpoint'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Agency coordination
    [
        'method' => 'POST',
        'path' => '/api/v1/events/{id}/agency-coordination',
        'handler' => [EventController::class, 'addAgencyCoordination'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Update agency coordination
    [
        'method' => 'PUT',
        'path' => '/api/v1/events/agency-coordination/{coordination_id}',
        'handler' => [EventController::class, 'updateAgencyCoordination'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Integration checkpoint
    [
        'method' => 'POST',
        'path' => '/api/v1/events/{id}/integration/{subsystem}',
        'handler' => [EventController::class, 'integrationCheckpoint'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Get Law Enforcement incidents (integration example)
    [
        'method' => 'GET',
        'path' => '/api/v1/events/integrations/law-enforcement/incidents',
        'handler' => [EventController::class, 'getLawEnforcementIncidents'],
        'middleware' => JWTMiddleware::class,
    ],
    
    // Sync Law Enforcement incidents
    [
        'method' => 'POST',
        'path' => '/api/v1/events/integrations/law-enforcement/sync',
        'handler' => [EventController::class, 'syncLawEnforcementIncidents'],
        'middleware' => JWTMiddleware::class,
    ],
];
