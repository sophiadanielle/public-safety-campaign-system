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

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Config/db_connect.php';

header('Content-Type: application/json');

$env = static fn(string $key, $default = null) => getenv($key) !== false ? getenv($key) : $default;
$jwtSecret   = $env('JWT_SECRET', 'changeme_jwt_secret');
$jwtIssuer   = $env('JWT_ISSUER', 'public-safety-campaign');
$jwtAudience = $env('JWT_AUDIENCE', 'public-safety-clients');
$jwtExpiry   = (int) $env('JWT_EXPIRY_SECONDS', 3600);

// Load routes
$routes = array_merge(
    require __DIR__ . '/../src/Routes/auth.php',
    require __DIR__ . '/../src/Routes/campaigns.php',
    require __DIR__ . '/../src/Routes/content.php',
    require __DIR__ . '/../src/Routes/segments.php',
    require __DIR__ . '/../src/Routes/events.php',
    require __DIR__ . '/../src/Routes/surveys.php',
    require __DIR__ . '/../src/Routes/impact.php',
    require __DIR__ . '/../src/Routes/partners.php',
    require __DIR__ . '/../src/Routes/integrations.php',
    require __DIR__ . '/../src/Routes/automl.php',
);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

