<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\AiRecommendationSchemaService;

function getDbConnection(): ?PDO
{
    $host = getenv('PROD_DB_HOST') ?: getenv('DB_HOST') ?: 'localhost';
    $port = getenv('PROD_DB_PORT') ?: getenv('DB_PORT') ?: '3306';
    $name = getenv('PROD_DB_NAME') ?: getenv('DB_NAME') ?: 'LGU';
    $user = getenv('PROD_DB_USER') ?: getenv('DB_USER') ?: 'root';
    $pass = getenv('PROD_DB_PASS') ?: getenv('DB_PASSWORD') ?: '';
    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        return null;
    }
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}

loadEnv(__DIR__ . '/../../.env');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pdo = getDbConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    AiRecommendationSchemaService::ensure($pdo);

    $recommendationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $tab = $_GET['tab'] ?? 'all';
    $mode = $_GET['mode'] ?? 'overview';

    $viewService = new \App\Services\AiRecommendationViewService($pdo);

    if ($recommendationId > 0) {
        $summary = $viewService->getSummary($recommendationId);
        if (!$summary) {
            http_response_code(404);
            echo json_encode(['error' => 'Recommendation not found']);
            exit;
        }

        if ($tab === 'budget') {
            $response = [
                'summary' => $summary,
                'budget' => $viewService->getBudgetItems($recommendationId),
                'budget_summary' => $viewService->getBudgetSummary($recommendationId),
                'budget_analysis' => $viewService->getBudgetAnalysis($recommendationId),
            ];
        } elseif ($tab === 'staff') {
            $staff = $viewService->getStaffParticipants($recommendationId);
            $response = [
                'summary' => $summary,
                'staff' => $staff,
                'staffing' => $staff['staffing'] ?? null,
            ];
        } elseif ($tab === 'partners') {
            $response = [
                'summary' => $summary,
                'partners' => $viewService->getMatchedPartners($recommendationId),
                'partner_suggestions' => $viewService->getPartnerSuggestions($recommendationId),
            ];
        } elseif ($tab === 'schedule') {
            $response = [
                'summary' => $summary,
                'schedule_phases' => $viewService->getSchedulePhases($recommendationId),
            ];
        } elseif ($tab === 'reports') {
            $response = [
                'summary' => $summary,
                'reports' => $viewService->getReportSnapshots($recommendationId),
            ];
        } else {
            $response = [
                'summary' => $summary,
                'budget' => $viewService->getBudgetItems($recommendationId),
                'budget_summary' => $viewService->getBudgetSummary($recommendationId),
                'budget_analysis' => $viewService->getBudgetAnalysis($recommendationId),
                'staff' => $viewService->getStaffParticipants($recommendationId),
                'partners' => $viewService->getMatchedPartners($recommendationId),
                'partner_suggestions' => $viewService->getPartnerSuggestions($recommendationId),
                'schedule_phases' => $viewService->getSchedulePhases($recommendationId),
                'reports' => $viewService->getReportSnapshots($recommendationId),
            ];
        }

        if (isset($response['partner_suggestions'])) {
            $response['suggestions'] = $response['partner_suggestions'];
        }
        if (isset($response['staff']) && !isset($response['staffing'])) {
            $response['staffing'] = $response['staff']['staffing'] ?? null;
        }
        if (isset($response['schedule_phases'])) {
            $response['schedule'] = $response['schedule_phases'];
        }

        echo json_encode([
            'success' => true,
            'recommendation_id' => $recommendationId,
            'tab' => $tab,
            'data' => $response,
            'meta' => [
                'planning_status' => $summary['planning_status'] ?? 'not_generated',
                'read_only' => true,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($mode === 'list') {
        $stmt = $pdo->query("
            SELECT id, campaign_title, main_trend, category, priority_level, priority_score, report_count,
                   planning_status, approval_status, budget_validation_status, created_at
            FROM campaign_department_ai_recommendations
            ORDER BY priority_score DESC
        ");
        $list = $stmt->fetchAll();
        echo json_encode(['success' => true, 'mode' => 'list', 'recommendations' => $list], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($mode === 'allocation') {
        $allocation = $viewService->getGovernmentAllocationOverview();
        echo json_encode(['success' => true, 'mode' => 'allocation', 'data' => $allocation], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid parameters. Provide id=N, mode=list, or mode=allocation']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
