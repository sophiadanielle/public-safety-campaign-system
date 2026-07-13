<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\RoleMiddleware;
use App\Services\AiRecommendationSchemaService;
use App\Services\AiRecommendationViewService;
use App\Services\BudgetValidator;
use App\Services\CampaignBudgetAllocationService;
use App\Services\CampaignStaffMatchingService;
use App\Services\CampaignPartnerMatchingService;
use App\Services\CampaignScheduleService;
use PDO;
use RuntimeException;

class AiRecommendationPlanningController
{
    private BudgetValidator $budgetValidator;
    private CampaignBudgetAllocationService $budgetAllocation;
    private CampaignStaffMatchingService $staffMatching;
    private CampaignPartnerMatchingService $partnerMatching;
    private CampaignScheduleService $scheduleService;
    private AiRecommendationViewService $viewService;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $this->budgetValidator = new BudgetValidator($this->pdo);
        $this->budgetAllocation = new CampaignBudgetAllocationService($this->pdo);
        $this->staffMatching = new CampaignStaffMatchingService($this->pdo);
        $this->partnerMatching = new CampaignPartnerMatchingService($this->pdo);
        $this->scheduleService = new CampaignScheduleService($this->pdo);
        $this->viewService = new AiRecommendationViewService($this->pdo);
    }

    public function generate(?array $user, array $params = [], ?array $inputOverride = null): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        AiRecommendationSchemaService::ensure($this->pdo);

        $input = $inputOverride ?? (json_decode(file_get_contents('php://input'), true) ?? []);
        $recommendationId = (int) ($input['recommendation_id'] ?? 0);
        $campaignId = (int) ($input['campaign_id'] ?? 0);

        if ($recommendationId <= 0) {
            http_response_code(422);
            return ['error' => 'recommendation_id is required'];
        }

        $rec = $this->requireRecommendation($recommendationId);
        if ($campaignId <= 0 && !empty($rec['converted_campaign_id'])) {
            $campaignId = (int) $rec['converted_campaign_id'];
        }
        $camp = $campaignId > 0 ? $this->findCampaign($campaignId) : null;

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET planning_status = 'processing', planning_started_at = NOW()
                WHERE id = ? AND planning_status NOT IN ('processing', 'completed', 'completed_with_warnings')
            ");
            $stmt->execute([$recommendationId]);
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                http_response_code(409);
                return ['error' => 'Recommendation is already being processed or is completed'];
            }

            $warnings = [];
            $snapshotResult = $this->generateReportSnapshots($recommendationId, $rec);
            if (!empty($snapshotResult['warnings'])) {
                $warnings = array_merge($warnings, $snapshotResult['warnings']);
            }

            $budgetResult = $this->budgetAllocation->generateEstimatedBudgetFromRecommendation($recommendationId, $rec, $camp);

            $validationResult = $this->budgetValidator->validate($recommendationId);
            $validationStatus = $budgetResult['budget_validation_status'] ?? $validationResult['status'];
            $this->budgetValidator->updateValidationStatus($recommendationId, $validationStatus);

            $participants = $this->staffMatching->getRecommendedParticipantsFromRecommendation($rec, $camp);
            $this->staffMatching->storeParticipants($recommendationId, $participants);

            $matches = $this->partnerMatching->matchPartnersFromRecommendation($rec, $camp);
            $this->partnerMatching->storeMatches($recommendationId, $matches);
            $this->partnerMatching->storeSuggestions($recommendationId, $matches['suggestions']);

            $schedule = $this->scheduleService->generateScheduleFromRecommendation($recommendationId, $rec, $camp);

            $planningStatus = 'completed';
            if (!empty($validationResult['errors']) || !empty($validationResult['flags']) || !empty($warnings)) {
                $planningStatus = 'completed_with_warnings';
            }

            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET planning_status = ?, planning_generated_at = NOW(),
                    planning_version = COALESCE(planning_version, 0) + 1,
                    planning_source = 'deterministic',
                    planning_error_code = NULL,
                    planning_error_message = ?,
                    last_recalculated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$planningStatus, empty($warnings) ? null : implode("\n", $warnings), $recommendationId]);

            $this->logAudit(
                $this->userId($user),
                'ai_recommendation',
                'generate_planning',
                $recommendationId,
                ['campaign_id' => $campaignId ?: null, 'status' => $planningStatus, 'warnings' => $warnings]
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Planning data generated',
                'recommendation_id' => $recommendationId,
                'planning_status' => $planningStatus,
                'budget_validation' => $validationResult,
                'budget_estimate' => $budgetResult,
                'staff_matched' => count($participants),
                'partners_matched' => count($matches['matched']),
                'partner_suggestions' => count($matches['suggestions']),
                'schedule_phases' => $schedule['total_phases'],
                'report_snapshots' => $snapshotResult['stored'],
                'warnings' => $warnings,
            ];
        } catch (RuntimeException $e) {
            $this->pdo->rollBack();
            $this->markPlanningFailed($recommendationId, 'validation_error', $e->getMessage());
            http_response_code(400);
            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->markPlanningFailed($recommendationId, 'generation_failed', $e->getMessage());
            error_log('Generate planning failed: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Planning generation failed: ' . $e->getMessage()];
        }
    }

    public function recalculate(?array $user, array $params = [], ?array $inputOverride = null): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        AiRecommendationSchemaService::ensure($this->pdo);

        $input = $inputOverride ?? (json_decode(file_get_contents('php://input'), true) ?? []);
        $recommendationId = (int) ($input['recommendation_id'] ?? 0);

        if ($recommendationId <= 0) {
            http_response_code(422);
            return ['error' => 'recommendation_id is required'];
        }

        $this->requireRecommendation($recommendationId);

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET planning_status = 'processing', planning_started_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$recommendationId]);
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                http_response_code(409);
                return ['error' => 'Already processing'];
            }

            $this->pdo->prepare('DELETE FROM campaign_ai_recommendation_budget_items WHERE recommendation_id = ?')->execute([$recommendationId]);
            $this->pdo->prepare('DELETE FROM campaign_ai_recommendation_partners WHERE recommendation_id = ?')->execute([$recommendationId]);
            $this->pdo->prepare('DELETE FROM campaign_ai_recommendation_partner_suggestions WHERE recommendation_id = ?')->execute([$recommendationId]);
            $this->pdo->prepare('DELETE FROM campaign_ai_recommendation_schedule_phases WHERE recommendation_id = ?')->execute([$recommendationId]);
            $this->pdo->prepare('DELETE FROM campaign_ai_report_snapshots WHERE recommendation_id = ?')->execute([$recommendationId]);
            $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET planning_status = 'not_generated'
                WHERE id = ?
            ")->execute([$recommendationId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            return ['error' => 'Recalculation cleanup failed: ' . $e->getMessage()];
        }

        $input['action'] = 'generate';
        return $this->generate($user, [], $input);
    }

    public function backfillMissing(?array $user, array $params = [], ?array $inputOverride = null): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        AiRecommendationSchemaService::ensure($this->pdo);

        $stmt = $this->pdo->query("
            SELECT id
            FROM campaign_department_ai_recommendations
            WHERE COALESCE(planning_status, 'not_generated') IN ('not_generated', 'failed')
            ORDER BY priority_score DESC, report_count DESC, id
        ");

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = (int) $row['id'];
            $previousCode = http_response_code();
            http_response_code(200);
            $result = $this->generate($user, [], ['recommendation_id' => $id, 'action' => 'generate']);
            $results[] = [
                'recommendation_id' => $id,
                'success' => !isset($result['error']),
                'result' => $result,
            ];
            http_response_code($previousCode ?: 200);
        }

        return [
            'success' => true,
            'processed' => count($results),
            'results' => $results,
        ];
    }

    public function approveBudget(?array $user): array
    {
        return $this->requireFinanceAdminAndExecute($user, function (int $recId, array $input) use ($user) {
            $budget = $input['approved_budget'] ?? null;
            if ($budget === null || !is_numeric($budget) || bccomp((string) $budget, '0', 2) <= 0) {
                http_response_code(422);
                return ['error' => 'A valid positive approved_budget amount is required'];
            }

            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET approved_budget = ?, approved_budget_by = ?, approved_budget_at = NOW(),
                    approval_status = 'approved'
                WHERE id = ?
            ");
            $stmt->execute([(string) $budget, $user['user_id'], $recId]);

            $this->logAudit($user['user_id'] ?? null, 'ai_recommendation', 'approve_budget', $recId, ['amount' => $budget]);

            return ['success' => true, 'message' => 'Budget approved', 'recommendation_id' => $recId, 'amount' => $budget];
        });
    }

    public function approveDates(?array $user): array
    {
        return $this->requireFinanceAdminAndExecute($user, function (int $recId, array $input) use ($user) {
            $startDate = $input['effective_start_date'] ?? $input['start_date'] ?? null;
            $endDate = $input['effective_end_date'] ?? $input['end_date'] ?? null;

            if (!$startDate || !$endDate) {
                http_response_code(422);
                return ['error' => 'effective_start_date and effective_end_date are required'];
            }

            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET effective_start_date = ?, effective_end_date = ?,
                    approved_start_date = ?, approved_end_date = ?,
                    approved_dates_by = ?, approved_dates_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$startDate, $endDate, $startDate, $endDate, $user['user_id'], $recId]);

            $this->logAudit($user['user_id'] ?? null, 'ai_recommendation', 'approve_dates', $recId, ['start' => $startDate, 'end' => $endDate]);

            return ['success' => true, 'message' => 'Dates approved', 'recommendation_id' => $recId];
        });
    }

    public function reject(?array $user): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $recId = (int) ($input['recommendation_id'] ?? 0);

        if ($recId <= 0) {
            http_response_code(422);
            return ['error' => 'recommendation_id is required'];
        }

        $stmt = $this->pdo->prepare("
            UPDATE campaign_department_ai_recommendations
            SET approval_status = 'rejected', approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['user_id'], $recId]);

        $this->logAudit($user['user_id'] ?? null, 'ai_recommendation', 'reject', $recId, []);

        return ['success' => true, 'message' => 'Recommendation rejected', 'recommendation_id' => $recId];
    }

    private function requireFinanceAdminAndExecute(?array $user, callable $fn): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        try {
            $userRole = RoleMiddleware::getUserRole($user, $this->pdo);
            $userRoleName = strtolower($userRole ?? '');
            if ((int) ($user['role_id'] ?? 0) !== 1 && $userRoleName !== 'barangay administrator') {
                http_response_code(403);
                return ['error' => 'Only Finance/Admin (role_id=1) can approve budget allocations'];
            }
        } catch (\Exception $e) {
            http_response_code(403);
            return ['error' => 'Access denied: ' . $e->getMessage()];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $recId = (int) ($input['recommendation_id'] ?? 0);

        if ($recId <= 0) {
            http_response_code(422);
            return ['error' => 'recommendation_id is required'];
        }

        $this->requireRecommendation($recId);

        return $fn($recId, $input);
    }

    private function requireRecommendation(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_ai_recommendations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rec) {
            throw new RuntimeException('Recommendation not found');
        }
        return $rec;
    }

    private function findCampaign(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaigns WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $camp = $stmt->fetch(PDO::FETCH_ASSOC);
        return $camp ?: null;
    }

    private function requireCampaign(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_campaigns WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $camp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$camp) {
            throw new RuntimeException('Campaign not found');
        }
        return $camp;
    }

    private function generateReportSnapshots(int $recommendationId, array $recommendation): array
    {
        $ids = $this->decodeList($recommendation['cluster_report_ids'] ?? $recommendation['source_report_ids'] ?? null);
        $category = strtolower((string) ($recommendation['category'] ?? 'crime'));
        $stored = 0;
        $warnings = [];

        if (empty($ids)) {
            $warnings[] = 'No cluster report IDs were stored for this recommendation.';
            return ['stored' => 0, 'warnings' => $warnings];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO campaign_ai_report_snapshots
                (recommendation_id, source_type, external_report_id, source_local_id,
                 incident_title, description, category, severity, incident_status,
                 report_date, report_datetime, location, barangay_or_area, source_system,
                 source_updated_at, synchronized_at, is_missing_from_source)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                 source_local_id = VALUES(source_local_id),
                 incident_title = VALUES(incident_title),
                 description = VALUES(description),
                 category = VALUES(category),
                 severity = VALUES(severity),
                 incident_status = VALUES(incident_status),
                 report_date = VALUES(report_date),
                 report_datetime = VALUES(report_datetime),
                 location = VALUES(location),
                 barangay_or_area = VALUES(barangay_or_area),
                 source_system = VALUES(source_system),
                 source_updated_at = VALUES(source_updated_at),
                 synchronized_at = NOW(),
                 is_missing_from_source = VALUES(is_missing_from_source)
        ");

        foreach ($ids as $rawId) {
            $normalized = $this->normalizeReportId($rawId, $category);
            if ($normalized['external_report_id'] === '') {
                $warnings[] = 'A supporting report ID could not be resolved.';
                continue;
            }

            $snapshot = $this->lookupSourceReport($normalized, $recommendation);
            if ($snapshot['is_missing_from_source']) {
                $warnings[] = 'Supporting report ' . $normalized['external_report_id'] . ' could not be fully restored from the source database.';
            }

            $stmt->execute([
                $recommendationId,
                $normalized['source_type'],
                $normalized['external_report_id'],
                $normalized['source_local_id'],
                $snapshot['incident_title'],
                $snapshot['description'],
                $snapshot['category'],
                $snapshot['severity'],
                $snapshot['incident_status'],
                $snapshot['report_date'],
                $snapshot['report_datetime'],
                $snapshot['location'],
                $snapshot['barangay_or_area'],
                $snapshot['source_system'],
                $snapshot['source_updated_at'],
                $snapshot['is_missing_from_source'] ? 1 : 0,
            ]);
            $stored++;
        }

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM campaign_ai_report_snapshots WHERE recommendation_id = ?');
        $countStmt->execute([$recommendationId]);
        $actualCount = (int) $countStmt->fetchColumn();
        $expectedCount = (int) ($recommendation['report_count'] ?? count($ids));
        if ($expectedCount > 0 && $actualCount !== $expectedCount) {
            $warnings[] = "Stored report snapshots ({$actualCount}) do not match recommendation report count ({$expectedCount}).";
        }

        return ['stored' => $stored, 'warnings' => $warnings];
    }

    private function lookupSourceReport(array $normalized, array $recommendation): array
    {
        $sourceType = $normalized['source_type'];
        $localId = $normalized['source_local_id'];
        $fallbackTitle = $normalized['title']
            ?: ($recommendation['main_trend'] ?? $recommendation['incident_category'] ?? 'Supporting report');

        if ($sourceType === 'crime' && $localId !== null && $this->tableExists('crime_department_crime_incidents')) {
            $stmt = $this->pdo->prepare('SELECT * FROM crime_department_crime_incidents WHERE id = ? LIMIT 1');
            $stmt->execute([$localId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    'incident_title' => $row['title'] ?? $row['incident_title'] ?? $row['crime_type'] ?? $fallbackTitle,
                    'description' => $row['description'] ?? $row['details'] ?? $row['narrative'] ?? null,
                    'category' => $row['category'] ?? $row['crime_type'] ?? $recommendation['category'] ?? 'crime',
                    'severity' => $row['severity'] ?? $row['priority'] ?? null,
                    'incident_status' => $row['status'] ?? $row['incident_status'] ?? null,
                    'report_date' => $this->dateOnly($row['report_date'] ?? $row['date_reported'] ?? $row['created_at'] ?? null),
                    'report_datetime' => $this->dateTimeValue($row['report_datetime'] ?? $row['date_reported'] ?? $row['created_at'] ?? null),
                    'location' => $row['location'] ?? $row['address'] ?? $row['barangay'] ?? null,
                    'barangay_or_area' => $row['barangay'] ?? $row['area'] ?? null,
                    'source_system' => 'crime_department',
                    'source_updated_at' => $this->dateTimeValue($row['updated_at'] ?? null),
                    'is_missing_from_source' => false,
                ];
            }
        }

        return [
            'incident_title' => $fallbackTitle,
            'description' => $recommendation['description'] ?? $recommendation['campaign_description'] ?? null,
            'category' => $recommendation['category'] ?? $sourceType,
            'severity' => $recommendation['priority_level'] ?? null,
            'incident_status' => null,
            'report_date' => $this->dateOnly($recommendation['latest_date'] ?? $recommendation['created_at'] ?? null),
            'report_datetime' => $this->dateTimeValue($recommendation['latest_date'] ?? $recommendation['created_at'] ?? null),
            'location' => $this->firstLocation($recommendation['affected_locations'] ?? null),
            'barangay_or_area' => $this->firstLocation($recommendation['affected_locations'] ?? null),
            'source_system' => $sourceType === 'disaster' ? 'emergency_api_snapshot_unavailable' : 'ai_recommendation_snapshot',
            'source_updated_at' => null,
            'is_missing_from_source' => true,
        ];
    }

    private function normalizeReportId(mixed $raw, string $defaultSource): array
    {
        $sourceType = in_array($defaultSource, ['crime', 'disaster'], true) ? $defaultSource : 'crime';
        $localId = null;
        $externalId = '';
        $title = null;

        if (is_array($raw)) {
            $sourceType = strtolower((string) ($raw['source_type'] ?? $raw['source'] ?? $sourceType));
            $localId = $raw['source_local_id'] ?? $raw['local_id'] ?? $raw['id'] ?? null;
            $externalId = (string) ($raw['external_report_id'] ?? $raw['external_id'] ?? $raw['id'] ?? '');
            $title = isset($raw['title']) ? (string) $raw['title'] : null;
        } else {
            $value = trim((string) $raw);
            if (preg_match('/^(crime|disaster|emergency)-(.+)$/i', $value, $m)) {
                $sourceType = strtolower($m[1]) === 'emergency' ? 'disaster' : strtolower($m[1]);
                $localId = $m[2];
                $externalId = strtolower($sourceType) . '-' . $m[2];
            } else {
                $localId = preg_match('/^\d+$/', $value) ? $value : null;
                $externalId = $value;
            }
        }

        if ($externalId === '' && $localId !== null) {
            $externalId = $sourceType . '-' . $localId;
        }
        if ($externalId !== '' && !str_contains($externalId, '-') && $localId !== null) {
            $externalId = $sourceType . '-' . $externalId;
        }

        return [
            'source_type' => in_array($sourceType, ['crime', 'disaster'], true) ? $sourceType : $defaultSource,
            'source_local_id' => $localId === null ? null : (string) $localId,
            'external_report_id' => $externalId,
            'title' => $title,
        ];
    }

    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }
        return [];
    }

    private function firstLocation(mixed $value): ?string
    {
        $locations = $this->decodeList($value);
        if (empty($locations)) {
            return null;
        }
        return is_scalar($locations[0]) ? (string) $locations[0] : null;
    }

    private function dateOnly(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $time = strtotime((string) $value);
        return $time ? date('Y-m-d', $time) : null;
    }

    private function dateTimeValue(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $time = strtotime((string) $value);
        return $time ? date('Y-m-d H:i:s', $time) : null;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function markPlanningFailed(int $recommendationId, string $code, string $message): void
    {
        if ($recommendationId <= 0) {
            return;
        }
        try {
            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET planning_status = 'failed', planning_error_code = ?, planning_error_message = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $message, $recommendationId]);
        } catch (\Throwable $e) {
            error_log('Unable to mark AI recommendation planning as failed: ' . $e->getMessage());
        }
    }

    private function userId(array $user): ?int
    {
        $id = $user['user_id'] ?? $user['id'] ?? null;
        return $id === null ? null : (int) $id;
    }

    private function logAudit(?int $userId, string $entityType, string $action, int $entityId, array $details = []): void
    {
        try {
            $columns = ['user_id', 'entity_type', 'action', 'entity_id', 'ip_address', 'created_at'];
            $values = [':user_id', ':entity_type', ':action', ':entity_id', ':ip_address', 'NOW()'];
            $params = [
                'user_id' => $userId,
                'entity_type' => $entityType,
                'action' => $action,
                'entity_id' => $entityId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ];

            if ($this->columnExists('campaign_department_audit_logs', 'details')) {
                $columns[] = 'details';
                $values[] = ':details';
                $params['details'] = json_encode($details);
            }
            if ($this->columnExists('campaign_department_audit_logs', 'user_agent')) {
                $columns[] = 'user_agent';
                $values[] = ':user_agent';
                $params['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? 'api', 0, 255);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO campaign_department_audit_logs (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')'
            );
            $stmt->execute($params);
        } catch (\Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
