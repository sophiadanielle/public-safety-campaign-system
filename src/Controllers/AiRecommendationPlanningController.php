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

    public function accept(?array $user, array $params = [], ?array $inputOverride = null): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        $this->enforceAcceptPermissions($user);

        AiRecommendationSchemaService::ensure($this->pdo);
        $this->ensureBudgetTable($this->pdo);
        $this->ensureEventsTable($this->pdo);

        $input = $inputOverride ?? (json_decode(file_get_contents('php://input'), true) ?? []);
        $recommendationId = (int) ($input['recommendation_id'] ?? 0);
        $forceReaccept = !empty($input['force']) || !empty($input['reaccept']);

        if ($recommendationId <= 0) {
            http_response_code(422);
            return ['error' => 'recommendation_id is required'];
        }

        try {
            $rec = $this->requireRecommendationForAccept($recommendationId, $forceReaccept);
            $userId = $this->userId($user);

            // Ensure planning items (budget, schedule, staff) exist before accepting
            $budgetCount = (int) $this->pdo->query("SELECT COUNT(*) FROM campaign_ai_recommendation_budget_items WHERE recommendation_id = " . (int) $recommendationId)->fetchColumn();
            $phaseCount = (int) $this->pdo->query("SELECT COUNT(*) FROM campaign_ai_recommendation_schedule_phases WHERE recommendation_id = " . (int) $recommendationId)->fetchColumn();

            if ($budgetCount === 0 || $phaseCount === 0 || in_array($rec['planning_status'] ?? '', ['not_generated', 'failed'], true)) {
                try {
                    // Generate planning internally
                    $camp = !empty($rec['converted_campaign_id']) ? $this->findCampaign((int) $rec['converted_campaign_id']) : null;
                    $this->budgetAllocation->generateEstimatedBudgetFromRecommendation($recommendationId, $rec, $camp);
                    $participants = $this->staffMatching->getRecommendedParticipantsFromRecommendation($rec, $camp);
                    $this->staffMatching->storeParticipants($recommendationId, $participants);
                    $matches = $this->partnerMatching->matchPartnersFromRecommendation($rec, $camp);
                    $this->partnerMatching->storeMatches($recommendationId, $matches);
                    $this->partnerMatching->storeSuggestions($recommendationId, $matches['suggestions']);
                    $this->scheduleService->generateScheduleFromRecommendation($recommendationId, $rec, $camp);
                    // Keep the original AI generation logic, but ensure its supporting report
                    // snapshots are also available when a recommendation is accepted directly.
                    $this->generateReportSnapshots($recommendationId, $rec);
                    $this->pdo->prepare("UPDATE campaign_department_ai_recommendations SET planning_status = 'completed' WHERE id = ?")->execute([$recommendationId]);
                    $rec = $this->requireRecommendationForAccept($recommendationId, true);
                } catch (\Throwable $genErr) {
                    error_log('On-the-fly planning generation error: ' . $genErr->getMessage());
                }
            }

            $this->pdo->beginTransaction();

            $campaignId = $this->insertAcceptedCampaign($rec, $userId);
            $budgetItems = $this->insertAcceptedBudgetItems($recommendationId, $campaignId, $userId);
            $staff = $this->insertAcceptedStaff($recommendationId, $userId);
            $events = $this->insertAcceptedEvents($recommendationId, $campaignId, $userId, $rec);
            $partners = $this->insertAcceptedPartners($recommendationId, $campaignId);

            // The All Campaigns modal now uses the same 8-step planner for View/Edit.
            // Copy the accepted AI plan into those campaign-linked planner tables so
            // every AI recommendation becomes a complete approved 8-tab campaign.
            $manualPlan = $this->copyAcceptedRecommendationToCampaignPlanner(
                $recommendationId,
                $campaignId,
                $userId,
                $rec
            );

            $stmt = $this->pdo->prepare("
                UPDATE campaign_department_ai_recommendations
                SET converted_campaign_id = ?, approval_status = 'accepted',
                    accepted_at = NOW(), accepted_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$campaignId, $userId, $recommendationId]);

            $this->logAudit(
                $userId,
                'ai_recommendation',
                'accept',
                $recommendationId,
                [
                    'campaign_id' => $campaignId,
                    'budget_items' => $budgetItems,
                    'staff_created' => $staff['created'],
                    'events_created' => $events,
                    'partners_created' => $partners,
                ]
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Recommendation accepted and converted into a campaign',
                'recommendation_id' => $recommendationId,
                'campaign_id' => $campaignId,
                'budget_items_created' => $budgetItems,
                'staff_created' => $staff['created'],
                'staff_skipped_existing' => $staff['skipped'],
                'events_created' => $events,
                'partners_created' => $partners,
                'reports_copied' => $manualPlan['reports'],
                'participants_copied' => $manualPlan['participants'],
                'audience_segments_linked' => $manualPlan['audiences'],
                'schedule_phases_copied' => $manualPlan['phases'],
            ];
        } catch (RuntimeException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $code = http_response_code();
            if ($code === 200 || $code < 400) {
                $code = 400;
            }
            http_response_code($code);
            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('AI recommendation accept failed: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Acceptance failed: ' . $e->getMessage()];
        }
    }

    private function enforceAcceptPermissions(array $user): void
    {
        try {
            $userRoleName = strtolower((string) (RoleMiddleware::getUserRole($user, $this->pdo) ?? ''));
        } catch (\Exception $e) {
            $userRoleName = '';
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        $directRole = strtolower((string) ($user['role'] ?? $user['user_type'] ?? ''));

        if ($userRoleName === 'viewer' || $directRole === 'viewer' || str_contains($directRole, 'viewer')) {
            http_response_code(403);
            throw new RuntimeException('Viewer role is read-only. You cannot accept recommendations.');
        }

        if ($roleId === 1) {
            return;
        }

        $allowedRoles = ['admin', 'staff', 'secretary', 'kagawad', 'captain', 'barangay administrator', 'barangay staff', 'system_admin', 'barangay_admin', 'campaign_creator'];
        if (!$userRoleName || !in_array($userRoleName, $allowedRoles, true)) {
            if (!$directRole || !in_array($directRole, $allowedRoles, true)) {
                http_response_code(403);
                throw new RuntimeException('Insufficient permissions. Only authorized LGU personnel can accept recommendations.');
            }
        }
    }

    private function requireRecommendationForAccept(int $id, bool $allowReaccept = true): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM campaign_department_ai_recommendations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rec) {
            http_response_code(444);
            throw new RuntimeException('Recommendation not found');
        }
        if (!empty($rec['converted_campaign_id']) && !$allowReaccept) {
            http_response_code(409);
            throw new RuntimeException('This recommendation has already been accepted and converted into campaign #' . (int) $rec['converted_campaign_id']);
        }
        return $rec;
    }

    private function insertAcceptedCampaign(array $rec, ?int $userId): int
    {
        $locations = $this->decodeList($rec['affected_locations'] ?? null);
        $locationText = $this->firstLocation($rec['affected_locations'] ?? null);
        if ($locationText === null && count($locations) > 1) {
            $locationText = implode(', ', array_slice($locations, 0, 2));
        }

        $startDate = $rec['effective_start_date'] ?? null;
        $endDate = $rec['effective_end_date'] ?? null;

        $phaseRange = $this->schedulePhaseRange($rec['id'] ?? 0);
        if (!$startDate && $phaseRange['min']) {
            $startDate = $phaseRange['min'];
        }
        if (!$endDate && $phaseRange['max']) {
            $endDate = $phaseRange['max'];
        }
        if (!$startDate) {
            $startDate = $this->dateOnly($rec['earliest_date'] ?? null) ?: date('Y-m-d', strtotime('+2 days'));
        }
        if (!$endDate) {
            $duration = max(1, (int) ($rec['recommended_duration'] ?? 30));
            $endDate = date('Y-m-d', strtotime($startDate . ' +' . $duration . ' days'));
        }

        $budget = $rec['final_recommended_budget'] ?? $rec['estimated_budget'] ?? null;
        if ($budget !== null) {
            $budget = (float) $budget;
        }

        $acceptedCategory = $this->acceptedCampaignCategory($rec);

        $stmt = $this->pdo->prepare("
            INSERT INTO campaign_department_campaigns
                (title, description, category, geographic_scope, status,
                 start_date, end_date, ai_recommended_datetime, owner_id,
                 objectives, location, assigned_staff, barangay_target_zones,
                 budget, staff_count)
            VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim((string) ($rec['campaign_title'] ?? '')),
            $rec['campaign_description'] ?? $rec['description'] ?? null,
            $acceptedCategory,
            'barangay',
            $startDate,
            $endDate,
            $this->dateTimeValue($rec['planning_generated_at'] ?? null),
            $userId,
            $this->actionsAsText($rec['ai_recommended_actions'] ?? null),
            $locationText,
            $this->assignedStaffSnapshot($rec['id'] ?? 0),
            $this->jsonOrNull($locations),
            $budget,
            $this->countMatchedStaff($rec['id'] ?? 0),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function acceptedCampaignCategory(array $rec): string
    {
        $trend = strtolower(trim((string) ($rec['trend_key'] ?? '')));
        $source = $this->recommendationSourceType($rec);

        if ($source === 'disaster' || str_starts_with($trend, 'disaster:')) {
            return 'disaster';
        }

        if (str_contains($trend, 'youth-safety') || str_contains($trend, 'drug-related')) {
            return 'education';
        }

        if (preg_match('/violent|homicide|assault|domestic|sexual|kidnapp|robbery|theft|burglary|vehicle-theft|carnapp|public-disorder|vandalism/', $trend)) {
            return 'crime';
        }

        $actions = $this->decodeList($rec['ai_recommended_actions'] ?? null);
        $text = strtolower(trim(implode(' ', array_filter([
            (string) ($rec['campaign_title'] ?? ''),
            (string) ($rec['ai_target_audience'] ?? ''),
            implode(' ', array_map(static fn($v) => is_scalar($v) ? (string) $v : '', $actions)),
        ]))));

        $educational = (bool) preg_match('/\b(awareness|educat|seminar|workshop|training|orientation|leadership|peer-led|peer education|student|students|school|schools|youth|kabataan|prevention program|information campaign|responsible citizenship)\b/u', $text);
        $enforcement = (bool) preg_match('/\b(patrol|enforcement|apprehend|apprehension|arrest|surveillance|hotspot operation|police operation|checkpoint|raid|law enforcement)\b/u', $text);

        return ($educational && !$enforcement) ? 'education' : 'crime';
    }

    private function recommendationSourceType(array $recommendation): string
    {
        $ids = $this->decodeList($recommendation['cluster_report_ids'] ?? $recommendation['source_report_ids'] ?? null);
        foreach ($ids as $raw) {
            if (is_array($raw)) {
                $source = strtolower(trim((string) ($raw['source_type'] ?? $raw['source'] ?? '')));
                if ($source === 'emergency') $source = 'disaster';
                if (in_array($source, ['crime', 'disaster'], true)) {
                    return $source;
                }
            }
        }

        $trend = strtolower((string) ($recommendation['trend_key'] ?? ''));
        if (str_starts_with($trend, 'disaster:')) {
            return 'disaster';
        }

        // Backward compatibility for older recommendations where category was
        // also used as the source field.
        $legacy = strtolower((string) ($recommendation['category'] ?? ''));
        return $legacy === 'disaster' ? 'disaster' : 'crime';
    }

    private function assignedStaffSnapshot(int $recommendationId): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT staff_name_snapshot, staff_role_snapshot, required_role
            FROM campaign_ai_recommendation_participants
            WHERE recommendation_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$recommendationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return null;
        }
        $snapshot = [];
        foreach ($rows as $row) {
            $name = $row['staff_name_snapshot'] ?: ('AI Recommended - ' . $row['required_role']);
            $snapshot[] = $name;
        }
        return json_encode($snapshot);
    }

    private function insertAcceptedBudgetItems(int $recommendationId, int $campaignId, ?int $userId): int
    {
        $stmt = $this->pdo->query("
            SELECT * FROM campaign_ai_recommendation_budget_items
            WHERE recommendation_id = " . (int) $recommendationId . "
            ORDER BY sort_order ASC, id ASC
        ");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($items)) {
            return 0;
        }

        $insert = $this->pdo->prepare("
            INSERT INTO campaign_budgets
                (campaign_id, item_name, item_type, quantity, unit_cost, funding_source,
                 notes, created_by, source_recommendation_id, category, item_description,
                 sessions_or_days, unit_label, related_action, recommendation_reason,
                 pricing_source, pricing_confidence, calculation_basis, is_estimate, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = 0;
        foreach ($items as $item) {
            $quantity = (int) round((float) ($item['quantity'] ?? 1));
            if ($quantity < 1) {
                $quantity = 1;
            }
            $insert->execute([
                $campaignId,
                trim((string) ($item['item_name'] ?? 'Budget item')),
                $item['item_type'] ?? 'consumable',
                $quantity,
                (float) ($item['unit_cost'] ?? 0),
                $item['funding_source'] ?? 'estimated_need',
                $item['recommendation_reason'] ?? $item['description'] ?? null,
                $userId,
                $recommendationId,
                $item['category'] ?? null,
                $item['description'] ?? null,
                $item['sessions_or_days'] ?? null,
                $item['unit_label'] ?? null,
                $item['related_action'] ?? null,
                $item['recommendation_reason'] ?? null,
                $item['pricing_source'] ?? null,
                $item['pricing_confidence'] ?? null,
                $item['calculation_basis'] ?? null,
                $item['is_estimate'] !== null ? (int) $item['is_estimate'] : 1,
                (int) ($item['sort_order'] ?? 0),
            ]);
            $count++;
        }

        return $count;
    }

    private function insertAcceptedStaff(int $recommendationId, ?int $userId): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM campaign_ai_recommendation_participants
            WHERE recommendation_id = " . (int) $recommendationId . "
            ORDER BY id ASC
        ");
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $created = 0;
        $skipped = 0;

        $existsStmt = $this->pdo->prepare("
            SELECT id FROM campaign_department_reference_staff
            WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND LOWER(TRIM(role)) = LOWER(TRIM(?))
            LIMIT 1
        ");
        $insertStmt = $this->pdo->prepare("
            INSERT INTO campaign_department_reference_staff (name, role, qty)
            VALUES (?, ?, ?)
        ");
        $updateParticipantStmt = $this->pdo->prepare("
            UPDATE campaign_ai_recommendation_participants
            SET staff_id = ?, match_method = 'auto_accepted', confirmation_status = 'confirmed',
                is_confirmed = 1, confirmed_by = ?, confirmed_at = NOW()
            WHERE id = ?
        ");

        foreach ($participants as $participant) {
            if (!empty($participant['staff_id'])) {
                continue;
            }
            $role = trim((string) ($participant['required_role'] ?? ''));
            if ($role === '') {
                continue;
            }
            $name = 'AI Recommended - ' . $role;
            $qty = max(1, (int) ($participant['missing_qty'] ?? ($participant['required_qty'] ?? 1)));

            $existsStmt->execute([$name, $role]);
            $existing = $existsStmt->fetchColumn();

            if ($existing) {
                $updateParticipantStmt->execute([(int) $existing, $userId, (int) $participant['id']]);
                $skipped++;
                continue;
            }

            $insertStmt->execute([$name, $role, $qty]);
            $newStaffId = (int) $this->pdo->lastInsertId();
            $updateParticipantStmt->execute([$newStaffId, $userId, (int) $participant['id']]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function insertAcceptedEvents(int $recommendationId, int $campaignId, ?int $userId, array $rec): int
    {
        // Use the exact event/seminar proposals shown in the AI recommendation
        // "Events & Seminars" tab. This keeps what the user reviews in the AI
        // modal identical to what is created in public/events.php after Accept.
        $eventPlan = $this->viewService->getEventAndSeminarRecommendations($recommendationId);
        $recommendedEvents = is_array($eventPlan['recommended_events'] ?? null)
            ? $eventPlan['recommended_events']
            : [];

        // Defensive fallback: if the view service cannot build proposals, convert
        // the generated Date Sprint phases into event rows so an accepted campaign
        // never loses its scheduled activities.
        if (empty($recommendedEvents)) {
            $stmt = $this->pdo->prepare("\n                SELECT * FROM campaign_ai_recommendation_schedule_phases\n                WHERE recommendation_id = ?\n                ORDER BY sprint_number ASC, sort_order ASC, id ASC\n            ");
            $stmt->execute([$recommendationId]);
            $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($phases as $phase) {
                if (empty($phase['start_date'])) continue;
                $locations = $this->decodeList($phase['locations'] ?? null);
                $title = trim((string) ($phase['sprint_title'] ?? 'Campaign Activity')) ?: 'Campaign Activity';
                $recommendedEvents[] = [
                    'sequence' => (int) ($phase['sprint_number'] ?? (count($recommendedEvents) + 1)),
                    'event_title' => $title,
                    'event_type' => $this->eventTypeFromTitle($title),
                    'objective' => $phase['objectives'] ?? null,
                    'recommended_campaign_action' => null,
                    'hazard_focus' => $rec['incident_category'] ?? $rec['main_trend'] ?? $rec['category'] ?? 'Public safety',
                    'target_audience' => $rec['ai_target_audience'] ?? null,
                    'recommended_date' => $phase['start_date'],
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'recommended_venue_or_location' => is_scalar($locations[0] ?? null) ? (string) $locations[0] : ($this->firstLocation($rec['affected_locations'] ?? null) ?? 'Barangay San Agustin'),
                    'trainer_requirements' => null,
                    'equipment_requirements' => null,
                    'volunteer_requirements' => null,
                    'expected_output' => $phase['outputs'] ?? null,
                ];
            }
        }

        if (empty($recommendedEvents)) {
            return 0;
        }

        $exists = $this->pdo->prepare("\n            SELECT id FROM campaign_department_events\n            WHERE ai_recommendation_id = ? AND ai_sprint_number = ?\n            LIMIT 1\n        ");

        $insert = $this->pdo->prepare("\n            INSERT INTO campaign_department_events\n                (campaign_id, linked_campaign_id, name, event_name, event_title, title, event_type,\n                 description, event_description, hazard_focus, event_date, date, event_time, start_time, end_time,\n                 location, venue, starts_at, ends_at, status, event_status,\n                 transport_requirements, trainer_requirements, equipment_requirements, volunteer_requirements,\n                 post_event_notes, created_by, ai_recommendation_id, ai_sprint_number, ai_objectives)\n            VALUES\n                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 'scheduled',\n                 ?, ?, ?, ?, ?, ?, ?, ?, ?)\n        ");

        $count = 0;
        foreach ($recommendedEvents as $index => $event) {
            $sequence = max(1, (int) ($event['sequence'] ?? ($index + 1)));
            $exists->execute([$recommendationId, $sequence]);
            if ($exists->fetchColumn()) {
                // Idempotency: refreshing/accept retries must not duplicate rows in
                // the Events & Seminars table.
                continue;
            }

            $date = $this->dateOnly($event['recommended_date'] ?? null);
            if (!$date) continue;

            $title = trim((string) ($event['event_title'] ?? 'Campaign Event')) ?: 'Campaign Event';
            $eventType = strtolower(trim((string) ($event['event_type'] ?? '')));
            if (!in_array($eventType, ['seminar', 'drill', 'workshop', 'orientation', 'meeting', 'other'], true)) {
                $eventType = $this->eventTypeFromTitle($title);
            }

            $startTime = trim((string) ($event['start_time'] ?? '09:00')) ?: '09:00';
            $endTime = trim((string) ($event['end_time'] ?? '17:00')) ?: '17:00';
            if (preg_match('/^\d{2}:\d{2}$/', $startTime)) $startTime .= ':00';
            if (preg_match('/^\d{2}:\d{2}$/', $endTime)) $endTime .= ':00';

            $location = trim((string) ($event['recommended_venue_or_location'] ?? ''));
            if ($location === '') {
                $location = $this->firstLocation($rec['affected_locations'] ?? null) ?? 'Barangay San Agustin';
            }

            $descriptionParts = [];
            if (!empty($event['objective'])) {
                $descriptionParts[] = 'Objective: ' . trim((string) $event['objective']);
            }
            if (!empty($event['recommended_campaign_action'])) {
                $descriptionParts[] = 'Recommended campaign action: ' . trim((string) $event['recommended_campaign_action']);
            }
            if (!empty($event['target_audience'])) {
                $descriptionParts[] = 'Target audience: ' . trim((string) $event['target_audience']);
            }
            if (!empty($event['expected_output'])) {
                $descriptionParts[] = 'Expected output: ' . trim((string) $event['expected_output']);
            }
            $description = implode("\n\n", $descriptionParts);

            $insert->execute([
                $campaignId,
                $campaignId,
                $title,
                $title,
                $title,
                $title,
                $eventType,
                $description ?: null,
                $description ?: null,
                $event['hazard_focus'] ?? $rec['main_trend'] ?? $rec['incident_category'] ?? $rec['category'] ?? null,
                $date,
                $date,
                $startTime,
                $startTime,
                $endTime,
                $location,
                $location,
                $date . ' ' . $startTime,
                $date . ' ' . $endTime,
                $event['transport_requirements'] ?? null,
                $event['trainer_requirements'] ?? null,
                $event['equipment_requirements'] ?? null,
                $event['volunteer_requirements'] ?? null,
                !empty($event['recommendation_reason']) ? trim((string) $event['recommendation_reason']) : null,
                $userId,
                $recommendationId,
                $sequence,
                $event['objective'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    private function ensureEventsTable(PDO $pdo): void
    {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS campaign_department_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NULL,
                    event_name VARCHAR(255) NULL,
                    event_title VARCHAR(255) NULL,
                    title VARCHAR(255) NULL,
                    event_type VARCHAR(100) NOT NULL DEFAULT 'seminar',
                    description TEXT NULL,
                    event_description TEXT NULL,
                    hazard_focus VARCHAR(255) NULL,
                    date DATE NULL,
                    event_date DATE NULL,
                    start_time TIME NULL DEFAULT '09:00:00',
                    event_time TIME NULL DEFAULT '09:00:00',
                    end_time TIME NULL DEFAULT '17:00:00',
                    location VARCHAR(255) NULL,
                    venue VARCHAR(255) NULL,
                    starts_at DATETIME NULL,
                    ends_at DATETIME NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'scheduled',
                    event_status VARCHAR(50) NOT NULL DEFAULT 'scheduled',
                    linked_campaign_id INT NULL,
                    campaign_id INT NULL,
                    facilitators LONGTEXT NULL,
                    logistics_json LONGTEXT NULL,
                    created_by INT NULL,
                    ai_recommendation_id INT NULL,
                    ai_sprint_number INT NULL,
                    ai_objectives TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_events_linked_campaign (linked_campaign_id),
                    INDEX idx_events_status (status),
                    INDEX idx_events_date (event_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            error_log('campaign_department_events table creation error: ' . $e->getMessage());
        }

        $columns = [
            'name' => 'VARCHAR(255) NULL',
            'event_name' => 'VARCHAR(255) NULL',
            'event_title' => 'VARCHAR(255) NULL',
            'title' => 'VARCHAR(255) NULL',
            'event_type' => "VARCHAR(100) NOT NULL DEFAULT 'seminar'",
            'description' => 'TEXT NULL',
            'event_description' => 'TEXT NULL',
            'hazard_focus' => 'VARCHAR(255) NULL',
            'date' => 'DATE NULL',
            'event_date' => 'DATE NULL',
            'start_time' => "TIME NULL DEFAULT '09:00:00'",
            'event_time' => "TIME NULL DEFAULT '09:00:00'",
            'end_time' => "TIME NULL DEFAULT '17:00:00'",
            'location' => 'VARCHAR(255) NULL',
            'venue' => 'VARCHAR(255) NULL',
            'starts_at' => 'DATETIME NULL',
            'ends_at' => 'DATETIME NULL',
            'status' => "VARCHAR(50) NOT NULL DEFAULT 'scheduled'",
            'event_status' => "VARCHAR(50) NOT NULL DEFAULT 'scheduled'",
            'linked_campaign_id' => 'INT NULL',
            'campaign_id' => 'INT NULL',
            'facilitators' => 'LONGTEXT NULL',
            'logistics_json' => 'LONGTEXT NULL',
            'created_by' => 'INT NULL',
            'ai_recommendation_id' => 'INT NULL',
            'ai_sprint_number' => 'INT NULL',
            'ai_objectives' => 'TEXT NULL',
            'transport_requirements' => 'TEXT NULL',
            'trainer_requirements' => 'TEXT NULL',
            'equipment_requirements' => 'TEXT NULL',
            'volunteer_requirements' => 'TEXT NULL',
            'post_event_notes' => 'TEXT NULL',
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->columnExists('campaign_department_events', $name)) {
                try {
                    $pdo->exec("ALTER TABLE campaign_department_events ADD COLUMN {$name} {$definition}");
                } catch (\Throwable $e) {
                    error_log('campaign_department_events column ' . $name . ' failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function insertAcceptedPartners(int $recommendationId, int $campaignId): int
    {
        $count = 0;

        $matchedStmt = $this->pdo->query("
            SELECT * FROM campaign_ai_recommendation_partners
            WHERE recommendation_id = " . (int) $recommendationId . "
            ORDER BY id ASC
        ");
        $matched = $matchedStmt->fetchAll(PDO::FETCH_ASSOC);

        $insertPartner = $this->pdo->prepare("
            INSERT INTO campaign_department_partners (name, organization_type, contact_details)
            VALUES (?, ?, ?)
        ");
        $insertEngagement = $this->pdo->prepare("
            INSERT INTO campaign_department_partner_engagements (partner_id, campaign_id, engagement_type, notes)
            VALUES (?, ?, 'ai_recommended', ?)
        ");
        $updateMatched = $this->pdo->prepare("
            UPDATE campaign_ai_recommendation_partners
            SET partner_id = ?, is_confirmed = 1, confirmed_at = NOW()
            WHERE id = ?
        ");

        foreach ($matched as $partner) {
            $partnerId = (int) ($partner['partner_id'] ?? 0);
            $orgType = $this->normalizePartnerOrgType($partner['organization_type_snapshot'] ?? null);
            $name = trim((string) ($partner['partner_name_snapshot'] ?? '')) ?: 'AI Recommended - ' . ucfirst($orgType);
            $details = $this->contactDetailsJson($partner['capability_match_basis'] ?? null, $partner['recommendation_reason'] ?? null);

            if ($partnerId <= 0) {
                $insertPartner->execute([$name, $orgType, $details]);
                $partnerId = (int) $this->pdo->lastInsertId();
                $updateMatched->execute([$partnerId, (int) $partner['id']]);
            }

            $engagementMeta = json_encode([
                'source' => 'ai_recommendation',
                'recommendation_id' => $recommendationId,
                'role' => $partner['recommended_role'] ?? '',
                'engagement_type' => 'collaboration',
                'notes' => $partner['recommendation_reason'] ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $insertEngagement->execute([$partnerId, $campaignId, $engagementMeta]);
            $count++;
        }

        $suggestionStmt = $this->pdo->query("
            SELECT * FROM campaign_ai_recommendation_partner_suggestions
            WHERE recommendation_id = " . (int) $recommendationId . "
              AND proposal_status = 'proposed' AND created_partner_id IS NULL
            ORDER BY id ASC
        ");
        $suggestions = $suggestionStmt->fetchAll(PDO::FETCH_ASSOC);

        $updateSuggestion = $this->pdo->prepare("
            UPDATE campaign_ai_recommendation_partner_suggestions
            SET created_partner_id = ?, proposal_status = 'created', reviewed_at = NOW()
            WHERE id = ?
        ");

        foreach ($suggestions as $suggestion) {
            $orgType = $this->normalizePartnerOrgType($suggestion['organization_type'] ?? null);
            $name = 'AI Recommended - ' . ucfirst($orgType);
            $details = $this->contactDetailsJson($suggestion['rationale'] ?? null, $suggestion['expected_contribution'] ?? null);
            $insertPartner->execute([$name, $orgType, $details]);
            $partnerId = (int) $this->pdo->lastInsertId();
            $updateSuggestion->execute([$partnerId, (int) $suggestion['id']]);
            $engagementMeta = json_encode([
                'source' => 'ai_recommendation',
                'recommendation_id' => $recommendationId,
                'role' => $suggestion['expected_contribution'] ?? '',
                'engagement_type' => 'collaboration',
                'notes' => $suggestion['rationale'] ?? '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $insertEngagement->execute([$partnerId, $campaignId, $engagementMeta]);
            $count++;
        }

        return $count;
    }

    /**
     * Copy an accepted AI recommendation into the campaign-linked tables read by the
     * 8-step Plan/View/Edit Campaign modal. AI generation itself is intentionally
     * unchanged; this is only the acceptance/conversion bridge.
     */
    private function copyAcceptedRecommendationToCampaignPlanner(
        int $recommendationId,
        int $campaignId,
        ?int $userId,
        array $rec
    ): array {
        // Some older recommendations were generated before report snapshots existed.
        $snapshotCountStmt = $this->pdo->prepare('SELECT COUNT(*) FROM campaign_ai_report_snapshots WHERE recommendation_id = ?');
        $snapshotCountStmt->execute([$recommendationId]);
        if ((int) $snapshotCountStmt->fetchColumn() === 0) {
            try {
                $this->generateReportSnapshots($recommendationId, $rec);
            } catch (\Throwable $e) {
                error_log('AI accept report snapshot generation warning: ' . $e->getMessage());
            }
        }

        $reports = $this->copyAiReportsToCampaign($recommendationId, $campaignId, $userId);
        $participants = $this->copyAiParticipantsToCampaign($recommendationId, $campaignId, $userId);
        $phases = $this->copyAiScheduleToCampaign($recommendationId, $campaignId, $userId);
        $audiences = $this->copyAiAudienceToCampaign($recommendationId, $campaignId, $rec);

        // Keep the compatibility fields used by older campaign screens synchronized.
        $staffStmt = $this->pdo->prepare("SELECT staff_name_snapshot, selected_qty FROM campaign_department_campaign_participants WHERE campaign_id = ? ORDER BY id");
        $staffStmt->execute([$campaignId]);
        $staffRows = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
        $staffNames = [];
        $staffCount = 0;
        foreach ($staffRows as $row) {
            if (!empty($row['staff_name_snapshot'])) {
                $staffNames[] = $row['staff_name_snapshot'];
            }
            $staffCount += max(0, (int) ($row['selected_qty'] ?? 0));
        }

        $this->pdo->prepare("UPDATE campaign_department_campaigns SET assigned_staff = ?, staff_count = ?, status = 'approved' WHERE id = ?")
            ->execute([
                empty($staffNames) ? null : json_encode(array_values(array_unique($staffNames)), JSON_UNESCAPED_UNICODE),
                $staffCount,
                $campaignId,
            ]);

        return [
            'reports' => $reports,
            'participants' => $participants,
            'audiences' => $audiences,
            'phases' => $phases,
        ];
    }

    private function copyAiReportsToCampaign(int $recommendationId, int $campaignId, ?int $userId): int
    {
        $this->pdo->prepare('DELETE FROM campaign_department_campaign_reports WHERE campaign_id = ?')->execute([$campaignId]);

        $stmt = $this->pdo->prepare('SELECT * FROM campaign_ai_report_snapshots WHERE recommendation_id = ? ORDER BY report_datetime ASC, id ASC');
        $stmt->execute([$recommendationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insert = $this->pdo->prepare("\n            INSERT INTO campaign_department_campaign_reports\n                (campaign_id, report_title, report_type, report_date, location, description,\n                 original_file_name, stored_file_name, file_path, mime_type, file_size, uploaded_by)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', 'application/x-ai-report-snapshot', 0, ?)\n        ");

        $count = 0;
        foreach ($rows as $row) {
            $source = ucfirst((string) ($row['source_type'] ?? 'AI'));
            $externalId = trim((string) ($row['external_report_id'] ?? $row['id'] ?? ''));
            $fileLabel = $source . ' Report Snapshot' . ($externalId !== '' ? ' - ' . $externalId : '');
            $descriptionParts = [];
            if (!empty($row['description'])) $descriptionParts[] = (string) $row['description'];
            if (!empty($row['severity'])) $descriptionParts[] = 'Severity: ' . $row['severity'];
            if (!empty($row['incident_status'])) $descriptionParts[] = 'Incident status: ' . $row['incident_status'];
            if (!empty($row['source_system'])) $descriptionParts[] = 'Source: ' . $row['source_system'];
            if ($externalId !== '') $descriptionParts[] = 'Source report ID: ' . $externalId;

            $insert->execute([
                $campaignId,
                trim((string) ($row['incident_title'] ?? 'Supporting AI report')) ?: 'Supporting AI report',
                $source . ' Report',
                $row['report_date'] ?? $this->dateOnly($row['report_datetime'] ?? null),
                $row['location'] ?? $row['barangay_or_area'] ?? null,
                implode("\n\n", $descriptionParts),
                $fileLabel,
                $fileLabel,
                $userId,
            ]);
            $count++;
        }
        return $count;
    }

    private function copyAiParticipantsToCampaign(int $recommendationId, int $campaignId, ?int $userId): int
    {
        $this->pdo->prepare('DELETE FROM campaign_department_campaign_participants WHERE campaign_id = ?')->execute([$campaignId]);

        $stmt = $this->pdo->prepare("\n            SELECT p.*, s.name AS current_staff_name, s.role AS current_staff_role\n            FROM campaign_ai_recommendation_participants p\n            LEFT JOIN campaign_department_reference_staff s ON s.id = p.staff_id\n            WHERE p.recommendation_id = ?\n            ORDER BY p.id ASC\n        ");
        $stmt->execute([$recommendationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insert = $this->pdo->prepare("\n            INSERT INTO campaign_department_campaign_participants\n                (campaign_id, staff_id, staff_name_snapshot, staff_role_snapshot, selected_qty,\n                 assigned_activity, deployment_location, notes, created_by)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)\n        ");

        $count = 0;
        foreach ($rows as $row) {
            $staffId = !empty($row['staff_id']) ? (int) $row['staff_id'] : null;
            $name = $row['current_staff_name'] ?? $row['staff_name_snapshot'] ?? null;
            $role = $row['current_staff_role'] ?? $row['staff_role_snapshot'] ?? $row['required_role'] ?? null;
            $qty = (int) ($row['selected_qty'] ?? 0);
            if ($qty <= 0) $qty = (int) ($row['matched_qty'] ?? 0);
            if ($qty <= 0) $qty = (int) ($row['required_qty'] ?? 1);
            $qty = max(1, $qty);

            $notes = [];
            if (!empty($row['recommendation_reason'])) $notes[] = (string) $row['recommendation_reason'];
            if (!empty($row['required_capability'])) $notes[] = 'Capability: ' . $row['required_capability'];
            if (!empty($row['match_method'])) $notes[] = 'AI match: ' . $row['match_method'];

            $insert->execute([
                $campaignId,
                $staffId,
                $name,
                $role,
                $qty,
                $row['assigned_activity'] ?? null,
                $row['deployment_location'] ?? null,
                implode("\n", $notes),
                $userId,
            ]);
            $count++;
        }
        return $count;
    }

    private function copyAiScheduleToCampaign(int $recommendationId, int $campaignId, ?int $userId): int
    {
        $this->pdo->prepare('DELETE FROM campaign_department_campaign_schedule_phases WHERE campaign_id = ?')->execute([$campaignId]);

        $stmt = $this->pdo->prepare('SELECT * FROM campaign_ai_recommendation_schedule_phases WHERE recommendation_id = ? ORDER BY sort_order ASC, sprint_number ASC, id ASC');
        $stmt->execute([$recommendationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insert = $this->pdo->prepare("\n            INSERT INTO campaign_department_campaign_schedule_phases\n                (campaign_id, sprint_number, sprint_title, start_date, end_date, duration_days, objectives,\n                 activities, assigned_staff, assigned_partners, locations, phase_budget, outputs,\n                 completion_criteria, dependencies, status, sort_order, created_by)\n            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?)\n        ");

        $count = 0;
        foreach ($rows as $i => $row) {
            $start = $this->dateOnly($row['start_date'] ?? null);
            $end = $this->dateOnly($row['end_date'] ?? null);
            if (!$start || !$end) continue;
            $duration = max(1, (int) ($row['duration_days'] ?? 0));
            if ($duration <= 1) {
                $duration = max(1, (int) floor((strtotime($end) - strtotime($start)) / 86400) + 1);
            }
            $insert->execute([
                $campaignId,
                $i + 1,
                trim((string) ($row['sprint_title'] ?? 'Campaign Sprint')) ?: 'Campaign Sprint',
                $start,
                $end,
                $duration,
                $row['objectives'] ?? null,
                $this->jsonOrNull($row['activities'] ?? null),
                $this->jsonOrNull($row['assigned_staff'] ?? null),
                $this->jsonOrNull($row['assigned_partners'] ?? null),
                $this->jsonOrNull($row['locations'] ?? null),
                max(0, (float) ($row['phase_budget'] ?? 0)),
                $row['outputs'] ?? null,
                $row['completion_criteria'] ?? null,
                $this->jsonOrNull($row['dependencies'] ?? null),
                $i + 1,
                $userId,
            ]);
            $count++;
        }
        return $count;
    }

    private function copyAiAudienceToCampaign(int $recommendationId, int $campaignId, array $rec): int
    {
        $this->pdo->prepare('DELETE FROM campaign_department_campaign_audience WHERE campaign_id = ?')->execute([$campaignId]);

        $audienceText = trim((string) ($rec['ai_target_audience'] ?? ''));
        if ($audienceText === '') {
            $audienceText = 'Residents and households in affected areas';
        }
        $location = $this->firstLocation($rec['affected_locations'] ?? null) ?: 'Barangay San Agustin';
        $priority = strtolower((string) ($rec['priority_level'] ?? 'medium'));
        $risk = $priority === 'high' || $priority === 'critical' ? 'High' : ($priority === 'low' ? 'Low' : 'Medium');
        $sectors = $this->inferAudienceSectors($audienceText);
        if (empty($sectors)) $sectors = ['Households'];

        $find = $this->pdo->prepare("\n            SELECT id FROM campaign_department_audience_segments\n            WHERE is_archived = 0 AND sector_type = ? AND LOWER(COALESCE(location_reference,'')) = LOWER(?)\n            ORDER BY id DESC LIMIT 1\n        ");
        $insertSegment = $this->pdo->prepare("\n            INSERT INTO campaign_department_audience_segments\n                (segment_name, geographic_scope, location_reference, sector_type, risk_level,\n                 basis_of_segmentation, criteria, is_archived)\n            VALUES (?, 'Barangay', ?, ?, ?, 'Incident pattern reference', ?, 0)\n        ");
        $link = $this->pdo->prepare('INSERT IGNORE INTO campaign_department_campaign_audience (campaign_id, segment_id) VALUES (?, ?)');

        $linked = 0;
        foreach ($sectors as $sector) {
            $find->execute([$sector, $location]);
            $segmentId = (int) $find->fetchColumn();
            if ($segmentId <= 0) {
                $segmentName = 'AI Target - ' . $sector . ' - Rec #' . $recommendationId;
                $criteria = json_encode([
                    'source' => 'ai_recommendation',
                    'recommendation_id' => $recommendationId,
                    'target_audience' => $audienceText,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $insertSegment->execute([$segmentName, $location, $sector, $risk, $criteria]);
                $segmentId = (int) $this->pdo->lastInsertId();
            }
            if ($segmentId > 0) {
                $link->execute([$campaignId, $segmentId]);
                $linked++;
            }
        }
        return $linked;
    }

    private function inferAudienceSectors(string $text): array
    {
        $t = strtolower($text);
        $map = [
            'Youth' => ['youth', 'teen', 'adolescent', 'young people'],
            'Senior Citizens' => ['senior', 'elderly', 'older adult'],
            'Schools' => ['school', 'student', 'teacher', 'campus'],
            'NGOs' => ['ngo', 'non-government', 'civil society'],
            'Person with Disabilities' => ['pwd', 'disability', 'disabled', 'persons with disabilities'],
            'Pregnant Women' => ['pregnant', 'pregnancy', 'expectant mother'],
            'Households' => ['resident', 'household', 'family', 'families', 'community', 'general public', 'women', 'men', 'homeowner'],
        ];
        $out = [];
        foreach ($map as $sector => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($t, $needle)) {
                    $out[] = $sector;
                    break;
                }
            }
        }
        return array_values(array_unique($out));
    }

    private function actionsAsText(mixed $value): ?string
    {
        $items = $this->decodeList($value);
        if (empty($items)) {
            $text = trim((string) ($value ?? ''));
            return $text !== '' ? $text : null;
        }
        $lines = [];
        foreach ($items as $item) {
            if (is_scalar($item)) {
                $text = trim((string) $item);
                if ($text !== '') $lines[] = '• ' . $text;
            }
        }
        return empty($lines) ? null : implode("\n", $lines);
    }

    private function contactDetailsJson(?string $a, ?string $b): ?string
    {
        $parts = [];
        if ($a) {
            $parts[] = $a;
        }
        if ($b) {
            $parts[] = $b;
        }
        return json_encode($parts);
    }

    private function eventTypeFromTitle(string $title): string
    {
        $lower = strtolower($title);
        if (str_contains($lower, 'drill')) {
            return 'drill';
        }
        if (str_contains($lower, 'seminar') || str_contains($lower, 'forum') || str_contains($lower, 'lecture') || str_contains($lower, 'training')) {
            return 'seminar';
        }
        if (str_contains($lower, 'workshop') || str_contains($lower, 'demo')) {
            return 'workshop';
        }
        if (str_contains($lower, 'orientation')) {
            return 'orientation';
        }
        if (str_contains($lower, 'meeting') || str_contains($lower, 'coordination')) {
            return 'meeting';
        }
        return 'other';
    }

    private function normalizePartnerOrgType(?string $type): string
    {
        $allowed = ['school', 'ngo', 'government', 'private', 'other'];
        $normalized = strtolower(trim((string) $type));
        return in_array($normalized, $allowed, true) ? $normalized : 'other';
    }

    private function schedulePhaseRange(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT MIN(start_date) AS min_date, MAX(end_date) AS max_date
            FROM campaign_ai_recommendation_schedule_phases
            WHERE recommendation_id = ?
        ");
        $stmt->execute([$recommendationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['min' => $row['min_date'] ?? null, 'max' => $row['max_date'] ?? null];
    }

    private function countMatchedStaff(int $recommendationId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(matched_qty), 0) + COALESCE(SUM(missing_qty), 0)
            FROM campaign_ai_recommendation_participants
            WHERE recommendation_id = ?
        ");
        $stmt->execute([$recommendationId]);
        return (int) $stmt->fetchColumn();
    }

    private function jsonOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return json_encode(array_values($value));
        }
        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            return json_encode($decoded);
        }
        return json_encode([(string) $value]);
    }

    private function ensureBudgetTable(PDO $pdo): void
    {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS campaign_budgets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    campaign_id INT NOT NULL,
                    item_name VARCHAR(255) NOT NULL,
                    item_type VARCHAR(50) NOT NULL DEFAULT 'consumable',
                    quantity INT NOT NULL DEFAULT 1,
                    unit_cost DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                    funding_source VARCHAR(50) NOT NULL DEFAULT 'government_allocated',
                    notes TEXT,
                    is_archived TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_by INT,
                    INDEX idx_campaign_id (campaign_id),
                    INDEX idx_funding_source (funding_source),
                    INDEX idx_is_archived (is_archived)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            error_log('campaign_budgets ensure failed: ' . $e->getMessage());
        }

        $columns = [
            'item_type' => "VARCHAR(50) NOT NULL DEFAULT 'consumable'",
            'funding_source' => "VARCHAR(50) NOT NULL DEFAULT 'government_allocated'",
            'source_recommendation_id' => 'INT NULL',
            'category' => 'VARCHAR(120) NULL',
            'item_description' => 'TEXT NULL',
            'sessions_or_days' => 'INT NULL',
            'unit_label' => 'VARCHAR(80) NULL',
            'related_action' => 'TEXT NULL',
            'recommendation_reason' => 'TEXT NULL',
            'pricing_source' => 'VARCHAR(120) NULL',
            'pricing_confidence' => 'VARCHAR(40) NULL',
            'calculation_basis' => 'TEXT NULL',
            'is_estimate' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'sort_order' => 'INT NOT NULL DEFAULT 0',
        ];
        foreach ($columns as $name => $definition) {
            if (!$this->columnExists('campaign_budgets', $name)) {
                try {
                    $pdo->exec("ALTER TABLE campaign_budgets ADD COLUMN {$name} {$definition}");
                } catch (\Throwable $e) {
                    error_log('campaign_budgets column ' . $name . ' failed: ' . $e->getMessage());
                }
            }
        }
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
        $sourceType = $this->recommendationSourceType($recommendation);
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
            $normalized = $this->normalizeReportId($rawId, $sourceType);
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

        // source_local_id maps to an integer primary key in the local source table.
        // External disaster feeds use opaque/hash IDs (for example md5-like values),
        // so those IDs belong only in external_report_id and must never be inserted
        // into campaign_ai_report_snapshots.source_local_id (INT in the LGU schema).
        $numericLocalId = null;
        if (is_int($localId)) {
            $numericLocalId = $localId;
        } elseif (is_string($localId) && preg_match('/^\d+$/', trim($localId))) {
            $numericLocalId = (int) trim($localId);
        } elseif (is_float($localId) && floor($localId) === $localId) {
            $numericLocalId = (int) $localId;
        }

        if ($externalId === '' && $localId !== null) {
            $externalId = $sourceType . '-' . (string) $localId;
        }
        if ($externalId !== '' && !str_contains($externalId, '-') && $numericLocalId !== null) {
            $externalId = $sourceType . '-' . $externalId;
        }

        return [
            'source_type' => in_array($sourceType, ['crime', 'disaster'], true) ? $sourceType : $defaultSource,
            'source_local_id' => $numericLocalId,
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
