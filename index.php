<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CampaignController;
use App\Controllers\ContentController;
use App\Controllers\SegmentController;
use App\Controllers\EventController;
use App\Controllers\SurveyController;
use App\Controllers\ImpactController;
use App\Controllers\PartnerController;
use App\Controllers\IntegrationController;
use App\Controllers\AutoMLController;
use App\Middleware\JWTMiddleware;

// index.php is in the root, so vendor is in the same directory
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Config/db_connect.php';

// Set JSON header early to prevent any HTML output
header('Content-Type: application/json; charset=utf-8');

// Suppress any warnings/notices that might output HTML
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors, we'll handle them in JSON

$env = static fn(string $key, $default = null) => getenv($key) !== false ? getenv($key) : $default;
$jwtSecret   = $env('JWT_SECRET', 'changeme_jwt_secret');
$jwtIssuer   = $env('JWT_ISSUER', 'public-safety-campaign');
$jwtAudience = $env('JWT_AUDIENCE', 'public-safety-clients');
$jwtExpiry   = (int) $env('JWT_EXPIRY_SECONDS', 3600);

// Load routes - index.php is in root, so paths are relative to root
$routes = array_merge(
    require __DIR__ . '/src/Routes/auth.php',
    require __DIR__ . '/src/Routes/campaigns.php',
    require __DIR__ . '/src/Routes/content.php',
    require __DIR__ . '/src/Routes/segments.php',
    require __DIR__ . '/src/Routes/events.php',
    require __DIR__ . '/src/Routes/surveys.php',
    require __DIR__ . '/src/Routes/impact.php',
    require __DIR__ . '/src/Routes/partners.php',
    require __DIR__ . '/src/Routes/integrations.php',
    require __DIR__ . '/src/Routes/automl.php',
    require __DIR__ . '/src/Routes/autocomplete.php',
);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Strip the script name from REQUEST_URI to match routes correctly
// If REQUEST_URI is /public-safety-campaign-system/index.php/api/v1/content
// We want to extract /api/v1/content
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);

// Remove script directory and script name from REQUEST_URI
if ($scriptDir !== '/' && $scriptDir !== '.') {
    if (strpos($requestUri, $scriptDir) === 0) {
        $requestUri = substr($requestUri, strlen($scriptDir));
    }
}

// Remove index.php if present
if (strpos($requestUri, '/index.php') === 0) {
    $requestUri = substr($requestUri, strlen('/index.php'));
} elseif (strpos($requestUri, 'index.php/') !== false) {
    $requestUri = substr($requestUri, strpos($requestUri, 'index.php/') + strlen('index.php'));
}

// Ensure it starts with /
if ($requestUri === '' || ($requestUri[0] !== '/' && $requestUri !== '')) {
    $requestUri = '/' . $requestUri;
}

// Normalize: remove trailing slashes except for root
if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}

// Simple path matcher supporting /{param}
$matched = null;
$params = [];
foreach ($routes as $route) {
    if ($route['method'] !== $method) {
        continue;
    }
    $pattern = preg_replace('#\{([\w]+)\}#', '(?P<$1>[^/]+)', $route['path']);
    $pattern = '#^' . $pattern . '$#';
    if (preg_match($pattern, $requestUri, $matches)) {
        $matched = $route;
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

try {
    // Middleware (JWT) if configured
    $user = null;
    if (isset($matched['middleware']) && $matched['middleware'] === JWTMiddleware::class) {
        $user = JWTMiddleware::authenticate($pdo, $jwtSecret, $jwtAudience, $jwtIssuer);
    }

    // Dispatch controller
    [$class, $action] = $matched['handler'];
    $controller = new $class($pdo, $jwtSecret, $jwtIssuer, $jwtAudience, $jwtExpiry);
    $response = $controller->$action($user, $params);

    if (is_array($response)) {
        echo json_encode($response);
    }
} catch (Throwable $e) {
    // Ensure we're still outputting JSON even on errors
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    
    // Log the full error for debugging
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return sanitized error message
    echo json_encode([
        'error' => $e->getMessage(),
        'type' => get_class($e)
    ]);
}

