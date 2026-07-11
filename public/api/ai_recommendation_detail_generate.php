<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\AiRecommendationPlanningController;
use App\Middleware\JWTMiddleware;
use App\Services\AiRecommendationSchemaService;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function ai_plan_load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function ai_plan_db(): ?PDO
{
    $host = getenv('PROD_DB_HOST') ?: getenv('DB_HOST') ?: 'localhost';
    $port = getenv('PROD_DB_PORT') ?: getenv('DB_PORT') ?: '3306';
    $name = getenv('PROD_DB_NAME') ?: getenv('DB_NAME') ?: 'LGU';
    $user = getenv('PROD_DB_USER') ?: getenv('DB_USER') ?: 'root';
    $pass = getenv('PROD_DB_PASS') ?: getenv('DB_PASSWORD') ?: '';

    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        error_log('AI recommendation generate DB connection failed: ' . $e->getMessage());
        return null;
    }
}

function ai_plan_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ai_plan_response(['success' => false, 'error' => 'POST is required'], 405);
}

ai_plan_load_env(__DIR__ . '/../../.env');

$pdo = ai_plan_db();
if (!$pdo) {
    ai_plan_response(['success' => false, 'error' => 'Database connection failed'], 500);
}

try {
    AiRecommendationSchemaService::ensure($pdo);

    $jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
    $jwtIssuer = getenv('JWT_ISSUER') ?: 'public-safety-campaign-system';
    $jwtAudience = getenv('JWT_AUDIENCE') ?: 'public-safety-campaign-system';
    $jwtExpirySeconds = (int) (getenv('JWT_EXPIRY_SECONDS') ?: 86400);
    $user = JWTMiddleware::authenticate($pdo, $jwtSecret, $jwtAudience, $jwtIssuer);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        ai_plan_response(['success' => false, 'error' => 'Invalid JSON request body'], 422);
    }

    $action = strtolower((string) ($input['action'] ?? 'generate'));
    $controller = new AiRecommendationPlanningController($pdo, $jwtSecret, $jwtIssuer, $jwtAudience, $jwtExpirySeconds);

    if ($action === 'backfill' || $action === 'backfill_missing') {
        $result = $controller->backfillMissing($user, [], $input);
    } elseif ($action === 'recalculate') {
        $result = $controller->recalculate($user, [], $input);
    } else {
        $result = $controller->generate($user, [], $input);
    }

    $status = http_response_code();
    if ($status < 400 && isset($result['error'])) {
        $status = 400;
    }
    ai_plan_response($result + ['success' => !isset($result['error'])], $status ?: 200);
} catch (RuntimeException $e) {
    ai_plan_response(['success' => false, 'error' => 'Planning generation failed: ' . $e->getMessage()], 401);
} catch (Throwable $e) {
    error_log('AI recommendation generation endpoint failed: ' . $e->getMessage());
    ai_plan_response(['success' => false, 'error' => 'Planning generation failed: ' . $e->getMessage()], 500);
}
