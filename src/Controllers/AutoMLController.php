<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AutoMLService;
use App\Middleware\RoleMiddleware;
use PDO;
use RuntimeException;

class AutoMLController
{
    private AutoMLService $service;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $this->service = new AutoMLService($pdo);
    }

    /**
     * Predict optimal schedule (existing method - enhanced)
     */
    public function predict(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : (int) ($params['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            http_response_code(422);
            return ['error' => 'campaign_id is required'];
        }

        $features = $input['features'] ?? [];
        try {
            $prediction = $this->service->predict($campaignId, $features);
            $id = $this->service->savePrediction($campaignId, $prediction);
            return [
                'prediction_id' => $id,
                'prediction' => $prediction,
            ];
        } catch (RuntimeException $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Predict conflict risk
     */
    public function predictConflict(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $entityType = $input['entity_type'] ?? 'campaign';
        $entityId = (int) ($input['entity_id'] ?? 0);
        $context = $input['context'] ?? [];

        if ($entityId <= 0) {
            http_response_code(422);
            return ['error' => 'entity_id is required'];
        }

        try {
            $prediction = $this->service->predictConflictRisk($entityType, $entityId, $context);
            return ['success' => true, 'prediction' => $prediction];
        } catch (\Exception $e) {
            error_log("AutoMLController::predictConflict - Error: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to predict conflict: ' . $e->getMessage()];
        }
    }

    /**
     * Predict engagement likelihood
     */
    public function predictEngagement(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $entityType = $input['entity_type'] ?? 'campaign';
        $entityId = (int) ($input['entity_id'] ?? 0);
        $context = $input['context'] ?? [];

        if ($entityId <= 0) {
            http_response_code(422);
            return ['error' => 'entity_id is required'];
        }

        try {
            $prediction = $this->service->predictEngagement($entityType, $entityId, $context);
            return ['success' => true, 'prediction' => $prediction];
        } catch (\Exception $e) {
            error_log("AutoMLController::predictEngagement - Error: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to predict engagement: ' . $e->getMessage()];
        }
    }

    /**
     * Forecast readiness
     */
    public function forecastReadiness(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = (int) ($input['campaign_id'] ?? $params['id'] ?? 0);

        if ($campaignId <= 0) {
            http_response_code(422);
            return ['error' => 'campaign_id is required'];
        }

        try {
            $prediction = $this->service->forecastReadiness($campaignId);
            return ['success' => true, 'prediction' => $prediction];
        } catch (\Exception $e) {
            error_log("AutoMLController::forecastReadiness - Error: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to forecast readiness: ' . $e->getMessage()];
        }
    }

    /**
     * Start training (Admin only)
     */
    public function startTraining(?array $user, array $params = []): array
    {
        if (!RoleMiddleware::requireRole($user, ['system_admin', 'barangay_admin'], $this->pdo)) {
            http_response_code(403);
            return ['error' => 'Only administrators can initiate model training'];
        }

        if (!$this->service->isTrainingConfigured()) {
            http_response_code(400);
            return ['error' => 'Google Cloud AutoML training is not configured. Set GOOGLE_CLOUD_PROJECT_ID and GOOGLE_SERVICE_ACCOUNT_KEY.'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $modelType = $input['model_type'] ?? null;
        $modelName = $input['model_name'] ?? null;
        $limit = isset($input['data_limit']) ? (int) $input['data_limit'] : null;

        if (!$modelType || !$modelName) {
            http_response_code(422);
            return ['error' => 'model_type and model_name are required'];
        }

        try {
            $trainingData = match($modelType) {
                'schedule_optimization' => $this->service->prepareScheduleOptimizationData($limit),
                'conflict_prediction' => $this->service->prepareConflictPredictionData($limit),
                'engagement_prediction' => $this->service->prepareEngagementPredictionData($limit),
                'readiness_forecast' => $this->service->prepareReadinessForecastData($limit),
                default => throw new RuntimeException("Invalid model type: $modelType"),
            };

            if (count($trainingData) < 100) {
                http_response_code(400);
                return ['error' => 'Insufficient training data. Need at least 100 examples, got ' . count($trainingData)];
            }

            $featureColumns = $this->service->getFeatureColumns($modelType);
            $targetColumn = $this->service->getTargetColumn($modelType);

            $modelVersion = $this->service->startTraining($modelType, $modelName, $trainingData, $targetColumn, $featureColumns, $user['id'] ?? null);

            return ['success' => true, 'message' => 'Training job started successfully', 'model_version' => $modelVersion];
        } catch (\Exception $e) {
            error_log("AutoMLController::startTraining - Error: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to start training: ' . $e->getMessage()];
        }
    }

    /**
     * Check training status
     */
    public function checkTrainingStatus(?array $user, array $params = []): array
    {
        $modelVersionId = (int) ($params['id'] ?? 0);
        if ($modelVersionId <= 0) {
            http_response_code(422);
            return ['error' => 'Invalid model version ID'];
        }

        try {
            $modelVersion = $this->service->checkTrainingStatus($modelVersionId);
            return ['model_version' => $modelVersion];
        } catch (RuntimeException $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Deploy model (Admin only)
     */
    public function deployModel(?array $user, array $params = []): array
    {
        if (!RoleMiddleware::requireRole($user, ['system_admin', 'barangay_admin'], $this->pdo)) {
            http_response_code(403);
            return ['error' => 'Only administrators can deploy models'];
        }

        $modelVersionId = (int) ($params['id'] ?? 0);
        if ($modelVersionId <= 0) {
            http_response_code(422);
            return ['error' => 'Invalid model version ID'];
        }

        try {
            $modelVersion = $this->service->deployModel($modelVersionId, $user['id'] ?? null);
            return ['success' => true, 'message' => 'Model deployed successfully', 'model_version' => $modelVersion];
        } catch (RuntimeException $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * List model versions
     */
    public function listModels(?array $user, array $params = []): array
    {
        $modelType = $_GET['model_type'] ?? null;
        $status = $_GET['status'] ?? null;
        $models = $this->service->listModelVersions($modelType, $status);
        return ['models' => $models];
    }

    /**
     * Get data preview
     */
    public function getDataPreview(?array $user, array $params = []): array
    {
        $modelType = $_GET['model_type'] ?? null;
        if (!$modelType) {
            http_response_code(422);
            return ['error' => 'model_type is required'];
        }

        try {
            $data = match($modelType) {
                'schedule_optimization' => $this->service->prepareScheduleOptimizationData(10),
                'conflict_prediction' => $this->service->prepareConflictPredictionData(10),
                'engagement_prediction' => $this->service->prepareEngagementPredictionData(10),
                'readiness_forecast' => $this->service->prepareReadinessForecastData(10),
                default => throw new RuntimeException("Invalid model type: $modelType"),
            };

            return [
                'sample_size' => count($data),
                'feature_columns' => $this->service->getFeatureColumns($modelType),
                'target_column' => $this->service->getTargetColumn($modelType),
                'sample_data' => array_slice($data, 0, 5),
            ];
        } catch (RuntimeException $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get AI insights for dashboard
     */
    public function getInsights(?array $user, array $params = []): array
    {
        try {
            // Get high-risk schedules
            $stmt = $this->pdo->query('
                SELECT c.id, c.title, c.start_date, c.status
                FROM `campaign_department_campaigns` c
                WHERE c.status IN ("draft", "scheduled") AND c.start_date > NOW()
                ORDER BY c.start_date ASC LIMIT 10
            ');
            $campaigns = $stmt->fetchAll() ?: [];

            $highRisk = [];
            foreach ($campaigns as $campaign) {
                try {
                    $conflict = $this->service->predictConflictRisk('campaign', $campaign['id']);
                    if (($conflict['risk_level'] ?? 'low') !== 'low') {
                        $highRisk[] = [
                            'campaign_id' => $campaign['id'],
                            'title' => $campaign['title'],
                            'start_date' => $campaign['start_date'],
                            'risk_level' => $conflict['risk_level'],
                            'conflict_probability' => $conflict['conflict_probability'],
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Get optimized upcoming events
            $stmt = $this->pdo->query('
                SELECT e.id, e.event_name, e.date, e.start_time, e.linked_campaign_id, c.title as campaign_title
                FROM `campaign_department_events` e LEFT JOIN `campaign_department_campaigns` c ON c.id = e.linked_campaign_id
                WHERE e.date >= CURDATE() AND e.event_status = "planned"
                ORDER BY e.date ASC, e.start_time ASC LIMIT 10
            ');

            // Get engagement trends
            $stmt2 = $this->pdo->query('
                SELECT DATE_FORMAT(c.start_date, "%Y-%m") as month,
                       COUNT(DISTINCT c.id) as campaign_count,
                       AVG((SELECT COUNT(*) FROM `campaign_department_attendance` a INNER JOIN `campaign_department_events` e ON e.id = a.event_id WHERE e.linked_campaign_id = c.id)) as avg_attendance
                FROM `campaign_department_campaigns` c
                WHERE c.start_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND c.status IN ("completed", "ongoing")
                GROUP BY DATE_FORMAT(c.start_date, "%Y-%m")
                ORDER BY month DESC LIMIT 6
            ');

            return [
                'high_risk_schedules' => $highRisk,
                'optimized_upcoming_events' => $stmt->fetchAll() ?: [],
                'engagement_trends' => $stmt2->fetchAll() ?: [],
            ];
        } catch (\Exception $e) {
            error_log("AutoMLController::getInsights - Error: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to get insights: ' . $e->getMessage()];
        }
    }
}





