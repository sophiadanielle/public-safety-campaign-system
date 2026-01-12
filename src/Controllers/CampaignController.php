<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AutoMLService;
use PDO;
use RuntimeException;

class CampaignController
{
    private AutoMLService $autoMLService;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $this->autoMLService = new AutoMLService($pdo);
    }

    public function index(?array $user, array $params = []): array
    {
        try {
            $sql = '
                SELECT id, title, description, category, geographic_scope, status, 
                       start_date, end_date, draft_schedule_datetime, ai_recommended_datetime, 
                       final_schedule_datetime, owner_id, created_at, objectives, location, 
                       assigned_staff, barangay_target_zones, budget, staff_count, materials_json 
                FROM campaign_department_campaigns 
                ORDER BY created_at DESC
            ';
            error_log('CRITICAL: CampaignController::index - Executing SQL: ' . $sql);
            $stmt = $this->pdo->query($sql);
            
            if ($stmt === false) {
                throw new \RuntimeException('Failed to execute query');
            }
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure all data is properly formatted for JSON
            // Note: null values are fine for JSON, no conversion needed
            // Just ensure the data structure is valid
            
            return ['data' => $data];
        } catch (\PDOException $e) {
            error_log('CRITICAL ERROR: CampaignController::index - Database error: ' . $e->getMessage());
            error_log('CRITICAL ERROR: CampaignController::index - SQL State: ' . $e->getCode());
            error_log('CRITICAL ERROR: CampaignController::index - Error Info: ' . json_encode($stmt->errorInfo() ?? []));
            http_response_code(500);
            return ['error' => 'Database error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            error_log('CampaignController::index - Error: ' . $e->getMessage());
            error_log('CampaignController::index - Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            return ['error' => 'Failed to load campaigns: ' . $e->getMessage()];
        }
    }

    public function store(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Log received input for debugging
        error_log('Campaign creation - Received input: ' . json_encode($input));
        
        // Use ONLY the values from input - no hardcoded defaults except for status
        $title = isset($input['title']) ? trim((string)$input['title']) : '';
        $description = isset($input['description']) ? trim((string)$input['description']) : null;
        $status = isset($input['status']) ? trim((string)$input['status']) : 'draft';
        $startDate = isset($input['start_date']) && $input['start_date'] ? trim((string)$input['start_date']) : null;
        $endDate = isset($input['end_date']) && $input['end_date'] ? trim((string)$input['end_date']) : null;
        $ownerId = $user['id'] ?? null;

        if (!$title) {
            http_response_code(422);
            return ['error' => 'Title is required'];
        }

        // Validate date range: start_date must not be later than end_date
        if ($startDate && $endDate) {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);
            if ($startTimestamp > $endTimestamp) {
                http_response_code(422);
                return ['error' => 'Start date must not be later than end date'];
            }
        }

        $allowedStatus = ['draft','pending','approved','ongoing','completed','scheduled','published','active','archived'];
        if (!in_array($status, $allowedStatus, true)) {
            http_response_code(422);
            return ['error' => 'Invalid status'];
        }

        // Use actual input values - no defaults
        $category = isset($input['category']) && $input['category'] ? trim((string)$input['category']) : null;
        $allowedCategories = ['fire', 'flood', 'earthquake', 'health', 'road safety'];
        if ($category && !in_array(strtolower($category), $allowedCategories, true)) {
            http_response_code(422);
            return ['error' => 'Invalid category. Must be one of: ' . implode(', ', $allowedCategories)];
        }
        $geographicScope = isset($input['geographic_scope']) && $input['geographic_scope'] ? trim((string)$input['geographic_scope']) : null;
        $objectives = isset($input['objectives']) && $input['objectives'] ? trim((string)$input['objectives']) : null;
        $location = isset($input['location']) && $input['location'] ? trim((string)$input['location']) : null;
        $assignedStaff = isset($input['assigned_staff']) && !empty($input['assigned_staff']) ? json_encode($input['assigned_staff']) : null;
        $barangayTargetZones = isset($input['barangay_target_zones']) && !empty($input['barangay_target_zones']) ? json_encode($input['barangay_target_zones']) : null;
        
        // Validate geographic scope is Quezon City only (soft validation - assumes all barangays are QC)
        // In production, validate against a barangay database to ensure they belong to Quezon City
        if ($geographicScope && stripos($geographicScope, 'quezon city') === false && stripos($geographicScope, 'qc') === false) {
            // Allow barangay names but note they should be validated against QC barangay list
        }
        // Use actual input values - convert to null if empty/zero
        $budget = isset($input['budget']) && $input['budget'] !== null && $input['budget'] !== '' ? (float) $input['budget'] : null;
        $staffCount = isset($input['staff_count']) && $input['staff_count'] !== null && $input['staff_count'] !== '' ? (int) $input['staff_count'] : null;
        $materialsJson = isset($input['materials_json']) && !empty($input['materials_json']) ? json_encode($input['materials_json']) : null;
        // NOTE: draft_schedule_datetime is NOT set during initial creation per sequence diagram
        // Schedule must be set via AI recommendation flow (Steps 3-9) - user requests prediction, then confirms
        // Ignore draft_schedule_datetime if provided during creation to enforce proper flow
        $draftSchedule = null;

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_department_campaigns (
                title, description, category, geographic_scope, status, 
                start_date, end_date, draft_schedule_datetime, owner_id, 
                objectives, location, assigned_staff, barangay_target_zones, 
                budget, staff_count, materials_json
            ) VALUES (
                :title, :description, :category, :geographic_scope, :status,
                :start_date, :end_date, :draft_schedule_datetime, :owner_id,
                :objectives, :location, :assigned_staff, :barangay_target_zones,
                :budget, :staff_count, :materials_json
            )
        ');
        $stmt->execute([
            'title' => $title,
            'description' => $description ?: null,
            'category' => $category ?: null,
            'geographic_scope' => $geographicScope ?: null,
            'status' => $status,
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
            'draft_schedule_datetime' => null, // Schedule must be set via AI recommendation flow (Steps 3-9)
            'owner_id' => $ownerId,
            'objectives' => $objectives ?: null,
            'location' => $location ?: null,
            'assigned_staff' => $assignedStaff,
            'barangay_target_zones' => $barangayTargetZones,
            'budget' => $budget,
            'staff_count' => $staffCount,
            'materials_json' => $materialsJson,
        ]);

        $campaignId = (int) $this->pdo->lastInsertId();

        // Log integrations to internal and external subsystems
        $this->logCampaignIntegrations($campaignId, [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'geographic_scope' => $geographicScope,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'draft_schedule_datetime' => $draftSchedule,
            'objectives' => $objectives,
            'location' => $location,
            'assigned_staff' => $assignedStaff ? json_decode($assignedStaff, true) : [],
            'barangay_target_zones' => $barangayTargetZones ? json_decode($barangayTargetZones, true) : [],
            'budget' => $budget,
            'staff_count' => $staffCount,
            'materials_json' => $materialsJson ? json_decode($materialsJson, true) : [],
        ]);

        // Log audit entry
        $this->logAudit($ownerId, 'campaign', 'create', $campaignId, ['title' => $title, 'status' => $status]);

        // Create notification for campaign creator
        try {
            \App\Controllers\NotificationController::create(
                $this->pdo,
                $ownerId,
                'campaign',
                'Campaign Created',
                "Campaign '{$title}' has been created successfully.",
                '/public/campaigns.php#list-section',
                'fas fa-bullhorn'
            );
        } catch (\Exception $e) {
            error_log('Failed to create notification: ' . $e->getMessage());
        }

        return ['id' => $campaignId, 'message' => 'Campaign created'];
    }

    public function show(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $campaign = $this->findCampaign($id);
        return ['data' => $campaign];
    }

    public function update(?array $user, array $params = []): array
    {
        $id = (int) ($params['id'] ?? 0);
        $this->findCampaign($id); // ensure exists

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $fields = [];
        $bindings = ['id' => $id];

        $allowedStatus = ['draft','pending','approved','ongoing','completed','scheduled','published','active','archived'];

        if (isset($input['title'])) {
            $fields[] = 'title = :title';
            $bindings['title'] = trim($input['title']);
        }
        if (isset($input['description'])) {
            $fields[] = 'description = :description';
            $bindings['description'] = trim((string) $input['description']) ?: null;
        }
        if (isset($input['status'])) {
            if (!in_array($input['status'], $allowedStatus, true)) {
                http_response_code(422);
                return ['error' => 'Invalid status'];
            }
            $fields[] = 'status = :status';
            $bindings['status'] = $input['status'];
        }
        if (isset($input['start_date'])) {
            $fields[] = 'start_date = :start_date';
            $bindings['start_date'] = $input['start_date'] ?: null;
        }
        if (isset($input['end_date'])) {
            $fields[] = 'end_date = :end_date';
            $bindings['end_date'] = $input['end_date'] ?: null;
        }
        
        // Validate date range if both dates are provided
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] && $input['end_date']) {
            $startTimestamp = strtotime($input['start_date']);
            $endTimestamp = strtotime($input['end_date']);
            if ($startTimestamp > $endTimestamp) {
                http_response_code(422);
                return ['error' => 'Start date must not be later than end date'];
            }
        }
        if (isset($input['objectives'])) {
            $fields[] = 'objectives = :objectives';
            $bindings['objectives'] = trim((string) $input['objectives']) ?: null;
        }
        if (isset($input['location'])) {
            $fields[] = 'location = :location';
            $bindings['location'] = trim((string) $input['location']) ?: null;
        }
        if (isset($input['assigned_staff'])) {
            $fields[] = 'assigned_staff = :assigned_staff';
            $bindings['assigned_staff'] = json_encode($input['assigned_staff']);
        }
        if (isset($input['barangay_target_zones'])) {
            $fields[] = 'barangay_target_zones = :barangay_target_zones';
            $bindings['barangay_target_zones'] = json_encode($input['barangay_target_zones']);
        }
        if (isset($input['budget'])) {
            $fields[] = 'budget = :budget';
            $bindings['budget'] = (float) $input['budget'];
        }
        if (isset($input['staff_count'])) {
            $fields[] = 'staff_count = :staff_count';
            $bindings['staff_count'] = (int) $input['staff_count'];
        }
        if (isset($input['materials_json'])) {
            $fields[] = 'materials_json = :materials_json';
            $bindings['materials_json'] = json_encode($input['materials_json']);
        }
        if (isset($input['category'])) {
            $allowedCategories = ['fire', 'flood', 'earthquake', 'health', 'road safety'];
            $category = trim($input['category']);
            if ($category && !in_array(strtolower($category), $allowedCategories, true)) {
                http_response_code(422);
                return ['error' => 'Invalid category. Must be one of: ' . implode(', ', $allowedCategories)];
            }
            $fields[] = 'category = :category';
            $bindings['category'] = $category ?: null;
        }
        if (isset($input['geographic_scope'])) {
            $fields[] = 'geographic_scope = :geographic_scope';
            $bindings['geographic_scope'] = trim($input['geographic_scope']) ?: null;
        }
        if (isset($input['draft_schedule_datetime'])) {
            $fields[] = 'draft_schedule_datetime = :draft_schedule_datetime';
            $bindings['draft_schedule_datetime'] = $input['draft_schedule_datetime'] ?: null;
        }

        if (empty($fields)) {
            return ['message' => 'Nothing to update'];
        }

        $sql = 'UPDATE campaign_department_campaigns SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        // Log audit entry
        $this->logAudit($user['id'] ?? null, 'campaign', 'update', $id, ['fields_updated' => array_keys($input)]);

        return ['message' => 'Campaign updated', 'id' => $id];
    }

    /**
     * List content items linked to a campaign
     */
    public function listContent(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $stmt = $this->pdo->prepare('
            SELECT ci.id, ci.title, ci.body, ci.content_type, ci.created_at
            FROM content_items ci
            WHERE ci.campaign_id = :cid
            ORDER BY ci.created_at DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function addSchedule(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $scheduledAt = $input['scheduled_at'] ?? null;
        $channel = trim($input['channel'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if (!$scheduledAt || !$channel) {
            http_response_code(422);
            return ['error' => 'scheduled_at and channel are required'];
        }

        // Insert schedule with status 'pending'
        $stmt = $this->pdo->prepare('INSERT INTO campaign_schedules (campaign_id, scheduled_at, channel, notes, status) VALUES (:campaign_id, :scheduled_at, :channel, :notes, :status)');
        $stmt->execute([
            'campaign_id' => $campaignId,
            'scheduled_at' => $scheduledAt,
            'channel' => $channel,
            'notes' => $notes ?: null,
            'status' => 'pending',
        ]);

        $scheduleId = (int) $this->pdo->lastInsertId();
        
        // Log audit entry
        $this->logAudit($user['id'] ?? null, 'campaign_schedule', 'create', $scheduleId, ['campaign_id' => $campaignId, 'scheduled_at' => $scheduledAt]);

        return ['id' => $scheduleId, 'message' => 'Schedule created'];
    }

    public function listSchedules(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        // Get schedules with status and last posting attempt from notification_logs
        $stmt = $this->pdo->prepare('
            SELECT 
                cs.id, 
                cs.scheduled_at, 
                cs.channel, 
                cs.notes, 
                cs.status,
                cs.created_at,
                MAX(nl.created_at) as last_posting_attempt
            FROM campaign_schedules cs
            LEFT JOIN notification_logs nl ON nl.campaign_id = cs.campaign_id 
                AND nl.channel = cs.channel 
                AND DATE(nl.created_at) = DATE(cs.scheduled_at)
            WHERE cs.campaign_id = :campaign_id 
            GROUP BY cs.id, cs.scheduled_at, cs.channel, cs.notes, cs.status, cs.created_at
            ORDER BY cs.scheduled_at ASC
        ');
        $stmt->execute(['campaign_id' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function sendSchedule(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $scheduleId = (int) ($params['sid'] ?? 0);

        $this->findCampaign($campaignId);
        $schedule = $this->findSchedule($campaignId, $scheduleId);

        try {
            // Simulate sending by inserting a notification_log entry and integration log
            $stmt = $this->pdo->prepare('INSERT INTO notification_logs (campaign_id, audience_member_id, channel, status, response_message) VALUES (:campaign_id, NULL, :channel, :status, :response_message)');
            $stmt->execute([
                'campaign_id' => $campaignId,
                'channel' => $schedule['channel'],
                'status' => 'sent',
                'response_message' => 'delivered',
            ]);

            $payload = [
                'campaign_id' => $campaignId,
                'schedule_id' => $scheduleId,
                'channel' => $schedule['channel'],
                'scheduled_at' => $schedule['scheduled_at'],
            ];
            $log = $this->pdo->prepare('INSERT INTO integration_logs (source, payload, status) VALUES (:source, :payload, :status)');
            $log->execute([
                'source' => 'notification_dispatch',
                'payload' => json_encode($payload),
                'status' => 'queued',
            ]);

            // Fire outbound webhook if configured
            $webhookUrl = getenv('NOTIFY_WEBHOOK_URL') ?: null;
            $webhookSuccess = true;
            if ($webhookUrl) {
                $webhookSuccess = $this->dispatchWebhook($webhookUrl, $payload);
            }

            // Update schedule status to 'sent' on success, 'failed' on failure
            $scheduleStatus = $webhookSuccess ? 'sent' : 'failed';
            $updateStmt = $this->pdo->prepare('UPDATE campaign_schedules SET status = :status WHERE id = :id');
            $updateStmt->execute([
                'id' => $scheduleId,
                'status' => $scheduleStatus,
            ]);

            // Log audit entry
            $this->logAudit($user['id'] ?? null, 'campaign_schedule', 'send', $scheduleId, ['campaign_id' => $campaignId, 'status' => $scheduleStatus]);

            return [
                'message' => $scheduleStatus === 'sent' ? 'Schedule sent successfully' : 'Schedule sent but webhook failed',
                'status' => $scheduleStatus,
                'notification_log_id' => (int) $this->pdo->lastInsertId(),
                'integration_log_id' => (int) $this->pdo->lastInsertId(),
            ];
        } catch (\Exception $e) {
            // Update schedule status to 'failed' on error
            $updateStmt = $this->pdo->prepare('UPDATE campaign_schedules SET status = :status WHERE id = :id');
            $updateStmt->execute([
                'id' => $scheduleId,
                'status' => 'failed',
            ]);

            http_response_code(500);
            return ['error' => 'Failed to send schedule: ' . $e->getMessage(), 'status' => 'failed'];
        }
    }

    public function listSegments(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $stmt = $this->pdo->prepare('
            SELECT s.id, s.name, s.criteria, s.created_at
            FROM campaign_audience ca
            INNER JOIN audience_segments s ON s.id = ca.segment_id
            WHERE ca.campaign_id = :cid
            ORDER BY s.created_at DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return ['data' => $stmt->fetchAll()];
    }

    public function syncSegments(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $segments = $input['segment_ids'] ?? null;
        if (!is_array($segments)) {
            http_response_code(422);
            return ['error' => 'segment_ids array is required'];
        }
        $segmentIds = array_values(array_unique(array_map('intval', $segments)));
        if (empty($segmentIds)) {
            http_response_code(422);
            return ['error' => 'At least one segment id is required'];
        }

        $this->assertSegments($segmentIds);

        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM campaign_audience WHERE campaign_id = :cid');
            $del->execute(['cid' => $campaignId]);

            $ins = $this->pdo->prepare('INSERT INTO campaign_audience (campaign_id, segment_id) VALUES (:cid, :sid)');
            foreach ($segmentIds as $sid) {
                $ins->execute(['cid' => $campaignId, 'sid' => $sid]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Failed to sync segments'];
        }

        return ['message' => 'Segments synced', 'count' => count($segmentIds)];
    }

    /**
     * Request AI-recommended posting time for a campaign
     */
    public function requestAIRecommendation(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $campaign = $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $features = $input['features'] ?? [];

        try {
            error_log("CampaignController::requestAIRecommendation - Campaign ID: $campaignId");
            error_log("CampaignController::requestAIRecommendation - Features: " . json_encode($features));
            
            $prediction = $this->autoMLService->predict($campaignId, $features);
            
            error_log("CampaignController::requestAIRecommendation - Prediction received:");
            error_log("  - Model Source: " . ($prediction['model_source'] ?? 'unknown'));
            error_log("  - Suggested DateTime: " . ($prediction['suggested_datetime'] ?? 'N/A'));
            error_log("  - Confidence Score: " . ($prediction['confidence_score'] ?? 'N/A'));
            error_log("  - AutoML Configured: " . (isset($prediction['automl_configured']) ? ($prediction['automl_configured'] ? 'YES' : 'NO') : 'UNKNOWN'));
            
            // Save AI recommendation to campaign
            $stmt = $this->pdo->prepare('
                UPDATE campaign_department_campaigns 
                SET ai_recommended_datetime = :ai_recommended_datetime 
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $campaignId,
                'ai_recommended_datetime' => $prediction['suggested_datetime'],
            ]);

            // Save prediction record
            try {
                $predictionId = $this->autoMLService->savePrediction($campaignId, $prediction);
            } catch (\Exception $e) {
                error_log("CampaignController::requestAIRecommendation - Failed to save prediction record: " . $e->getMessage());
                $predictionId = null;
            }

            $message = 'AI recommendation generated';
            if (isset($prediction['model_source'])) {
                if ($prediction['model_source'] === 'google_automl') {
                    $message = 'Google AutoML recommendation generated successfully';
                } elseif (isset($prediction['automl_configured']) && !$prediction['automl_configured']) {
                    $message = 'Heuristic recommendation generated (Google AutoML not configured)';
                } elseif (isset($prediction['fallback_reason'])) {
                    $message = 'Heuristic recommendation generated (Google AutoML unavailable)';
                }
            }

            return [
                'prediction_id' => $predictionId,
                'prediction' => $prediction,
                'message' => $message,
            ];
        } catch (RuntimeException $e) {
            error_log("CampaignController::requestAIRecommendation - RuntimeException: " . $e->getMessage());
            http_response_code(400);
            return ['error' => $e->getMessage()];
        } catch (\Exception $e) {
            error_log("CampaignController::requestAIRecommendation - Exception: " . $e->getMessage());
            error_log("CampaignController::requestAIRecommendation - Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return ['error' => 'Failed to generate AI recommendation: ' . $e->getMessage()];
        }
    }

    /**
     * Accept or override AI recommendation
     */
    public function setFinalSchedule(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $this->findCampaign($campaignId);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $finalSchedule = $input['final_schedule_datetime'] ?? null;
        $useAIRecommendation = $input['use_ai_recommendation'] ?? false;

        if ($useAIRecommendation) {
            // Use AI recommendation
            $stmt = $this->pdo->prepare('
                UPDATE campaign_department_campaigns 
                SET final_schedule_datetime = ai_recommended_datetime,
                    draft_schedule_datetime = ai_recommended_datetime,
                    status = CASE 
                        WHEN status = "draft" THEN "scheduled"
                        WHEN status IN ("pending", "approved") THEN "published"
                        ELSE status
                    END
                WHERE id = :id AND ai_recommended_datetime IS NOT NULL
            ');
            $stmt->execute(['id' => $campaignId]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                return ['error' => 'No AI recommendation available. Request one first.'];
            }
        } elseif ($finalSchedule) {
            // Override with manual schedule
            $stmt = $this->pdo->prepare('
                UPDATE campaign_department_campaigns 
                SET final_schedule_datetime = :final_schedule_datetime,
                    draft_schedule_datetime = :final_schedule_datetime,
                    status = CASE 
                        WHEN status = "draft" THEN "scheduled"
                        WHEN status IN ("pending", "approved") THEN "published"
                        ELSE status
                    END
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $campaignId,
                'final_schedule_datetime' => $finalSchedule,
            ]);
        } else {
            http_response_code(422);
            return ['error' => 'Either use_ai_recommendation or final_schedule_datetime is required'];
        }

        // Log audit entry for schedule approval
        $this->logAudit($user['id'] ?? null, 'campaign', 'schedule_approved', $campaignId, [
            'final_schedule_datetime' => $finalSchedule ?? 'ai_recommended',
            'use_ai_recommendation' => $useAIRecommendation
        ]);

        return ['message' => 'Final schedule set successfully'];
    }

    /**
     * Get calendar view of campaigns
     */
    public function calendar(?array $user, array $params = []): array
    {
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');

        $stmt = $this->pdo->prepare('
            SELECT 
                id, title, description, status, category,
                start_date, end_date, 
                draft_schedule_datetime, ai_recommended_datetime, final_schedule_datetime,
                location, geographic_scope
            FROM campaign_department_campaigns
            WHERE (start_date BETWEEN :start AND :end)
               OR (end_date BETWEEN :start AND :end)
               OR (start_date <= :start AND end_date >= :end)
               OR (final_schedule_datetime BETWEEN :start AND :end)
            ORDER BY start_date ASC, final_schedule_datetime ASC
        ');
        $stmt->execute(['start' => $startDate, 'end' => $endDate]);
        $campaigns = $stmt->fetchAll();

        return ['data' => $campaigns];
    }

    /**
     * Check for scheduling conflicts with events
     */
    public function checkConflicts(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $proposedDatetime = $input['proposed_datetime'] ?? null;

        if (!$proposedDatetime) {
            http_response_code(422);
            return ['error' => 'proposed_datetime is required'];
        }

        $campaign = $this->findCampaign($campaignId);
        $proposedDate = date('Y-m-d', strtotime($proposedDatetime));

        // Check for conflicts with other campaigns
        $stmt = $this->pdo->prepare('
            SELECT id, title, final_schedule_datetime, location
            FROM campaign_department_campaigns
            WHERE id != :id
              AND final_schedule_datetime IS NOT NULL
              AND DATE(final_schedule_datetime) = :proposed_date
              AND status IN ("scheduled", "approved", "ongoing")
        ');
        $stmt->execute(['id' => $campaignId, 'proposed_date' => $proposedDate]);
        $campaignConflicts = $stmt->fetchAll();

        // Check for conflicts with events and seminars
        $stmt = $this->pdo->prepare('
            SELECT e.id, e.name, e.event_type, e.event_date, e.event_time, e.venue, e.location
            FROM events e
            WHERE e.event_date = :proposed_date
              AND e.status IN ("scheduled", "ongoing")
        ');
        $stmt->execute(['proposed_date' => $proposedDate]);
        $eventConflicts = $stmt->fetchAll();

        $hasConflicts = !empty($campaignConflicts) || !empty($eventConflicts);

        return [
            'has_conflicts' => $hasConflicts,
            'campaign_conflicts' => $campaignConflicts,
            'event_conflicts' => $eventConflicts,
            'proposed_datetime' => $proposedDatetime,
        ];
    }

    private function findCampaign(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, title, description, category, geographic_scope, status, 
                   start_date, end_date, draft_schedule_datetime, 
                   ai_recommended_datetime, final_schedule_datetime, 
                   owner_id, objectives, location, assigned_staff, 
                   barangay_target_zones, budget, staff_count, materials_json 
            FROM campaign_department_campaigns 
            WHERE id = :id LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $campaign = $stmt->fetch();
        if (!$campaign) {
            http_response_code(404);
            throw new RuntimeException('Campaign not found');
        }
        return $campaign;
    }

    private function findSchedule(int $campaignId, int $scheduleId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, campaign_id, scheduled_at, channel, notes, status FROM campaign_schedules WHERE id = :sid AND campaign_id = :cid LIMIT 1');
        $stmt->execute(['sid' => $scheduleId, 'cid' => $campaignId]);
        $schedule = $stmt->fetch();
        if (!$schedule) {
            http_response_code(404);
            throw new RuntimeException('Schedule not found');
        }
        return $schedule;
    }

    /**
     * Re-send a failed schedule
     */
    public function resendSchedule(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        $scheduleId = (int) ($params['sid'] ?? 0);

        $this->findCampaign($campaignId);
        $schedule = $this->findSchedule($campaignId, $scheduleId);

        // Only allow re-sending failed schedules
        if ($schedule['status'] !== 'failed') {
            http_response_code(422);
            return ['error' => 'Can only re-send failed schedules. Current status: ' . $schedule['status']];
        }

        // Reset status to pending and call sendSchedule logic
        $updateStmt = $this->pdo->prepare('UPDATE campaign_schedules SET status = :status WHERE id = :id');
        $updateStmt->execute([
            'id' => $scheduleId,
            'status' => 'pending',
        ]);

        // Call sendSchedule logic
        return $this->sendSchedule($user, $params);
    }

    private function assertSegments(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audience_segments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $found = (int) $stmt->fetchColumn();
        if ($found !== count($ids)) {
            throw new RuntimeException('One or more segments not found');
        }
    }

    private function dispatchWebhook(string $url, array $payload): bool
    {
        $secret = getenv('NOTIFY_WEBHOOK_SECRET') ?: 'demo_secret';
        $json = json_encode($payload);
        $signature = hash_hmac('sha256', $json, $secret);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Signature: {$signature}\r\n",
                'content' => $json,
                'timeout' => 5,
            ],
        ];
        $ctx = stream_context_create($opts);
        try {
            $result = @file_get_contents($url, false, $ctx);
            return $result !== false;
        } catch (\Throwable $e) {
            // swallow to avoid breaking flow; logged via integration_logs
            return false;
        }
    }

    /**
     * Log audit entry for campaign operations
     */
    private function logAudit(?int $userId, string $entityType, string $action, int $entityId, array $details = []): void
    {
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'audit_logs'");
            if (!$check || $check->rowCount() === 0) {
                return;
            }

            $stmt = $this->pdo->prepare('
                INSERT INTO audit_logs (user_id, entity_type, action, entity_id, details, created_at)
                VALUES (:user_id, :entity_type, :action, :entity_id, :details, NOW())
            ');
            $stmt->execute([
                'user_id' => $userId,
                'entity_type' => $entityType,
                'action' => $action,
                'entity_id' => $entityId,
                'details' => json_encode($details),
            ]);
        } catch (\Throwable $e) {
            // Fail silently - audit logging should not break main operations
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }

    /**
     * Record integration events for newly created campaigns.
     * This captures both internal module coordination and
     * external systems such as notification and training.
     */
    private function logCampaignIntegrations(int $campaignId, array $payload): void
    {
        // Fail-safe: if integration_logs table is missing or any error occurs,
        // we don't want to block campaign creation. Just log and return.
        try {
            // Ensure integration_logs table exists
            $check = $this->pdo->query("SHOW TABLES LIKE 'integration_logs'");
            if (!$check || $check->rowCount() === 0) {
                return;
            }

            $stmt = $this->pdo->prepare('INSERT INTO integration_logs (source, payload, status) VALUES (:source, :payload, :status)');
        } catch (\Throwable $e) {
            error_log('logCampaignIntegrations init failed: ' . $e->getMessage());
            return;
        }

        // Core internal subsystems
        $sources = [
            // Content Repository (materials, themes, hazard type)
            'content_repository',
            // Target Audience Segmentation (risk profiles, segments)
            'target_audience_segmentation',
            // Event & Seminar Management (implementation logistics)
            'event_seminar_management',
            // School & NGO Collaboration (partner engagement)
            'school_ngo_collaboration',
            // Emergency Communication System (mass notification)
            'emergency_communication_system',
            // Disaster Preparedness Training and Simulation
            'training_and_simulation',
            // Community Policing & Surveillance (volunteers)
            'community_policing_surveillance',
            // Traffic & Transport Management (permits)
            'traffic_transport_management',
            // Fire & Rescue Services Management (scheduling)
            'fire_rescue_management',
        ];

        foreach ($sources as $source) {
            try {
                $stmt->execute([
                    'source' => $source,
                    'payload' => json_encode([
                        'campaign_id' => $campaignId,
                        'source' => $source,
                        'campaign' => $payload,
                    ]),
                    'status' => 'queued',
                ]);
            } catch (\Throwable $e) {
                // Log but don't interrupt main flow
                error_log('logCampaignIntegrations insert failed for source ' . $source . ': ' . $e->getMessage());
            }
        }
    }
}


