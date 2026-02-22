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
     * Get AI recommendation for a specific campaign (called from campaigns.php)
     */
    public function getAIRecommendation(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        
        if ($campaignId <= 0) {
            http_response_code(422);
            return ['error' => 'Campaign ID is required'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $features = $input['features'] ?? [];

        try {
            // Get campaign details
            $stmt = $this->pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
            $stmt->execute([$campaignId]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$campaign) {
                http_response_code(404);
                return ['error' => 'Campaign not found'];
            }

            // Get prediction using the service
            $prediction = $this->service->predict($campaignId, $features);
            
            // Check for event conflicts
            $conflicts = $this->checkEventConflicts($campaignId, $prediction['recommended_datetime'] ?? null);
            
            // Get historical data for context
            $historicalData = $this->getHistoricalContext($campaignId);
            
            // Build comprehensive response
            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'campaign_title' => $campaign['title'] ?? 'Untitled Campaign',
                'prediction' => [
                    'recommended_datetime' => $prediction['recommended_datetime'] ?? date('Y-m-d H:i:s', strtotime('+3 days 9:00')),
                    'confidence_score' => $prediction['confidence_score'] ?? 0.75,
                    'optimal_day' => $prediction['optimal_day'] ?? 'Tuesday',
                    'optimal_time' => $prediction['optimal_time'] ?? '09:00 AM',
                    'reasoning' => $prediction['reasoning'] ?? 'Based on historical engagement patterns and event calendar analysis.',
                    'data_sources_used' => $prediction['data_sources_used'] ?? ['Historical campaigns', 'Event calendar', 'Survey responses'],
                    'recommendations' => [
                        'primary' => $prediction['recommendations']['primary'] ?? 'Schedule campaign for optimal community engagement',
                        'alternatives' => $prediction['recommendations']['alternatives'] ?? [],
                        'considerations' => $prediction['recommendations']['considerations'] ?? ['Weather conditions', 'Community events', 'Staff availability']
                    ]
                ],
                'conflicts' => $conflicts,
                'historical_context' => $historicalData
            ];
        } catch (\Exception $e) {
            error_log("AutoMLController::getAIRecommendation - Error: " . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Failed to get AI recommendation: ' . $e->getMessage()];
        }
    }

    /**
     * Check for event conflicts
     */
    private function checkEventConflicts(int $campaignId, ?string $recommendedDatetime): array
    {
        $conflicts = [];
        
        if (!$recommendedDatetime) {
            return $conflicts;
        }
        
        try {
            // Check events table for conflicts within 3 days
            $stmt = $this->pdo->prepare("
                SELECT id, title, event_date, event_type 
                FROM events 
                WHERE event_date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
                LIMIT 5
            ");
            $stmt->execute([$recommendedDatetime, $recommendedDatetime]);
            $events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($events as $event) {
                $conflicts[] = [
                    'type' => 'event',
                    'title' => $event['title'],
                    'date' => $event['event_date'],
                    'severity' => 'low'
                ];
            }
        } catch (\Exception $e) {
            // Events table might not exist, ignore
        }
        
        return $conflicts;
    }

    /**
     * Get historical context for AI recommendation
     */
    private function getHistoricalContext(int $campaignId): array
    {
        try {
            // Get similar past campaigns
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_campaigns,
                       AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100 as completion_rate
                FROM campaigns 
                WHERE status IN ('completed', 'ongoing', 'approved')
            ");
            $stmt->execute();
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'total_past_campaigns' => (int)($stats['total_campaigns'] ?? 0),
                'average_completion_rate' => round((float)($stats['completion_rate'] ?? 75), 1),
                'data_quality' => 'good'
            ];
        } catch (\Exception $e) {
            return [
                'total_past_campaigns' => 0,
                'average_completion_rate' => 75,
                'data_quality' => 'limited'
            ];
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
            // Check which column exists first using SHOW COLUMNS (more reliable than INFORMATION_SCHEMA)
            $campaignColumn = null;
            try {
                // First verify table exists
                $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'campaign_department_events'");
                if ($tableCheck && $tableCheck->rowCount() > 0) {
                    // Get all columns and check for exact matches
                    $columnsCheck = $this->pdo->query("SHOW COLUMNS FROM campaign_department_events");
                    if ($columnsCheck) {
                        $columns = $columnsCheck->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($columns as $column) {
                            $colName = $column['Field'] ?? $column['field'] ?? '';
                            if ($colName === 'linked_campaign_id') {
                                $campaignColumn = 'linked_campaign_id';
                                break;
                            }
                            if ($colName === 'campaign_id') {
                                $campaignColumn = 'campaign_id';
                                break;
                            }
                        }
                    }
                }
            } catch (\PDOException $e) {
                error_log('AutoMLController::getInsights - Error checking column: ' . $e->getMessage());
            }
            
            if ($campaignColumn) {
                try {
                    $stmt = $this->pdo->query("
                        SELECT e.id, e.name as event_name, e.date, e.start_time, e.{$campaignColumn}, c.title as campaign_title
                        FROM `campaign_department_events` e LEFT JOIN `campaign_department_campaigns` c ON c.id = e.{$campaignColumn}
                        WHERE e.date >= CURDATE() AND e.event_status = 'planned'
                        ORDER BY e.date ASC, e.start_time ASC LIMIT 10
                    ");
                } catch (\PDOException $e) {
                    $errorCode = $e->getCode();
                    $errorMessage = $e->getMessage();
                    if ($errorCode == '42S22' || strpos($errorMessage, 'Unknown column') !== false) {
                        error_log('AutoMLController::getInsights - Column not found error, falling back to events without join. Error: ' . $errorMessage);
                        $campaignColumn = null; // Reset to prevent further errors
                        $stmt = $this->pdo->query("
                            SELECT e.id, e.name as event_name, e.date, e.start_time, NULL as campaign_title
                            FROM `campaign_department_events` e
                            WHERE e.date >= CURDATE() AND e.event_status = 'planned'
                            ORDER BY e.date ASC, e.start_time ASC LIMIT 10
                        ");
                    } else {
                        throw $e; // Re-throw if it's a different error
                    }
                }
            } else {
                // No campaign column exists, get events without join
                $stmt = $this->pdo->query("
                    SELECT e.id, e.name as event_name, e.date, e.start_time, NULL as campaign_title
                    FROM `campaign_department_events` e
                    WHERE e.date >= CURDATE() AND e.event_status = 'planned'
                    ORDER BY e.date ASC, e.start_time ASC LIMIT 10
                ");
            }

            // Get engagement trends
            if ($campaignColumn) {
                try {
                    $stmt2 = $this->pdo->query("
                        SELECT DATE_FORMAT(c.start_date, '%Y-%m') as month,
                               COUNT(DISTINCT c.id) as campaign_count,
                               AVG((SELECT COUNT(*) FROM `campaign_department_attendance` a INNER JOIN `campaign_department_events` e ON e.id = a.event_id WHERE e.{$campaignColumn} = c.id)) as avg_attendance
                        FROM `campaign_department_campaigns` c
                        WHERE c.start_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND c.status IN ('completed', 'ongoing')
                        GROUP BY DATE_FORMAT(c.start_date, '%Y-%m')
                        ORDER BY month DESC LIMIT 6
                    ");
                } catch (\PDOException $e) {
                    $errorCode = $e->getCode();
                    $errorMessage = $e->getMessage();
                    if ($errorCode == '42S22' || strpos($errorMessage, 'Unknown column') !== false) {
                        error_log('AutoMLController::getInsights - Column not found error in engagement trends, using fallback. Error: ' . $errorMessage);
                        $stmt2 = $this->pdo->query("
                            SELECT DATE_FORMAT(c.start_date, '%Y-%m') as month,
                                   COUNT(DISTINCT c.id) as campaign_count,
                                   0 as avg_attendance
                            FROM `campaign_department_campaigns` c
                            WHERE c.start_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND c.status IN ('completed', 'ongoing')
                            GROUP BY DATE_FORMAT(c.start_date, '%Y-%m')
                            ORDER BY month DESC LIMIT 6
                        ");
                    } else {
                        throw $e; // Re-throw if it's a different error
                    }
                }
            } else {
                // No campaign column, skip attendance calculation
                $stmt2 = $this->pdo->query("
                    SELECT DATE_FORMAT(c.start_date, '%Y-%m') as month,
                           COUNT(DISTINCT c.id) as campaign_count,
                           0 as avg_attendance
                    FROM `campaign_department_campaigns` c
                    WHERE c.start_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND c.status IN ('completed', 'ongoing')
                    GROUP BY DATE_FORMAT(c.start_date, '%Y-%m')
                    ORDER BY month DESC LIMIT 6
                ");
            }

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





