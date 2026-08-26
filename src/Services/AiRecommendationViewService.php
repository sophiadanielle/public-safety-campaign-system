<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class AiRecommendationViewService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getSummary(int $recommendationId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, campaign_title,
                   COALESCE(NULLIF(main_trend, ''), NULLIF(incident_category, ''), campaign_title) AS main_trend,
                   incident_category,
                   COALESCE(NULLIF(description, ''), NULLIF(campaign_description, '')) AS description,
                   category,
                   priority_level, priority_score, ai_recommended_actions, ai_reasoning,
                   ai_target_audience, scoring_breakdown, generated_by, report_count,
                   COALESCE(NULLIF(cluster_report_ids, ''), NULLIF(source_report_ids, '')) AS cluster_report_ids,
                   affected_locations, earliest_date, latest_date,
                   severity_score, frequency_score, recency_score, geographic_score,
                   estimated_budget, system_budget_max, final_recommended_budget,
                   approved_budget, approval_status, planning_status, planning_source,
                   planning_error_code, planning_error_message, planning_version, planning_generated_at,
                   budget_validation_status, effective_start_date, effective_end_date,
                   recommended_duration, converted_campaign_id, created_at
            FROM campaign_department_ai_recommendations
            WHERE id = ?
        ");
        $stmt->execute([$recommendationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['ai_recommended_actions'] = $this->decodeJson($row['ai_recommended_actions']);
        $row['scoring_breakdown'] = $this->decodeJson($row['scoring_breakdown']);
        $row['cluster_report_ids'] = $this->decodeJson($row['cluster_report_ids']);
        $row['affected_locations'] = $this->decodeJson($row['affected_locations']);

        return $row;
    }

    public function getRecommendationWithCampaign(int $recommendationId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, c.id AS campaign_id, c.title AS campaign_title,
                   c.status AS campaign_status, c.start_date AS campaign_start_date,
                   c.end_date AS campaign_end_date, c.barangay_target_zones,
                   c.budget AS campaign_budget_field
            FROM campaign_department_ai_recommendations r
            LEFT JOIN campaign_department_campaigns c ON c.id = r.converted_campaign_id
            WHERE r.id = ?
        ");
        $stmt->execute([$recommendationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $row['barangay_target_zones'] = $this->decodeJson($row['barangay_target_zones']);
        return $row;
    }

    public function getBudgetItems(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, item_name, description, category, item_type, unit_label,
                   quantity, unit_cost, sessions_or_days, subtotal,
                   funding_source, related_action, recommendation_reason,
                   pricing_source, pricing_confidence, calculation_basis,
                   is_estimate, validation_status, sort_order
            FROM campaign_ai_recommendation_budget_items
            WHERE recommendation_id = ?
            ORDER BY sort_order, id
        ");
        $stmt->execute([$recommendationId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalEstimated = '0';
        foreach ($items as &$item) {
            $totalEstimated = bcadd($totalEstimated, $item['subtotal'], 2);
        }
        unset($item);

        return [
            'items' => $items,
            'total_estimated' => $totalEstimated,
            'count' => count($items),
        ];
    }

    public function getBudgetSummary(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT funding_source, COUNT(*) AS item_count, SUM(subtotal) AS total
            FROM campaign_ai_recommendation_budget_items
            WHERE recommendation_id = ?
            GROUP BY funding_source
            ORDER BY total DESC
        ");
        $stmt->execute([$recommendationId]);
        $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'by_funding_source' => $sources,
        ];
    }

    public function getStaffParticipants(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.staff_id, p.staff_name_snapshot, p.staff_role_snapshot,
                   p.required_role, p.required_qty, p.matched_qty, p.missing_qty,
                   p.selected_qty, p.deployment_location, p.assigned_activity,
                   p.required_capability, p.staffing_priority, p.staffing_source,
                   p.confirmation_status,
                   p.match_method, p.recommendation_reason, p.availability_status,
                   p.conflict_status, p.conflict_note, p.is_confirmed,
                   p.confirmed_by, p.confirmed_at,
                   rs.name AS current_staff_name, rs.role AS current_staff_role
            FROM campaign_ai_recommendation_participants p
            LEFT JOIN campaign_department_reference_staff rs ON rs.id = p.staff_id
            WHERE p.recommendation_id = ?
            ORDER BY p.required_role, p.staff_id IS NULL, p.staff_role_snapshot
        ");
        $stmt->execute([$recommendationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalConfirmed = 0;
        $totalUnmatched = 0;
        $totalConflicts = 0;
        $existingStaff = [];
        $missingRequirements = [];
        $roleSummary = [];

        foreach ($rows as &$r) {
            $r['required_role'] = $r['required_role'] ?: ($r['staff_role_snapshot'] ?? 'Campaign Staff');
            $r['required_qty'] = max(1, (int) ($r['required_qty'] ?? 1));
            $r['matched_qty'] = max(0, (int) ($r['matched_qty'] ?? 0));
            $r['missing_qty'] = max(0, (int) ($r['missing_qty'] ?? 0));
            $r['selected_qty'] = max(0, (int) ($r['selected_qty'] ?? ($r['staff_id'] ? 1 : 0)));
            $r['confirmation_status'] = $r['confirmation_status'] ?: ((int) $r['is_confirmed'] === 1 ? 'confirmed' : 'pending');

            if ((int) $r['is_confirmed'] === 1) $totalConfirmed++;
            if ($r['match_method'] === 'unmatched' || $r['match_method'] === 'staffing_gap') $totalUnmatched++;
            if ($r['conflict_status'] === 'possible_conflict' || $r['conflict_status'] === 'confirmed_conflict') $totalConflicts++;

            $roleKey = mb_strtolower(trim((string) $r['required_role']));
            if (!isset($roleSummary[$roleKey])) {
                $roleSummary[$roleKey] = [
                    'required_qty' => $r['required_qty'],
                    'matched_qty' => $r['matched_qty'],
                    'missing_qty' => $r['missing_qty'],
                ];
            } else {
                $roleSummary[$roleKey]['required_qty'] = max($roleSummary[$roleKey]['required_qty'], $r['required_qty']);
                $roleSummary[$roleKey]['matched_qty'] = max($roleSummary[$roleKey]['matched_qty'], $r['matched_qty']);
                $roleSummary[$roleKey]['missing_qty'] = max($roleSummary[$roleKey]['missing_qty'], $r['missing_qty']);
            }

            if ($r['staff_id'] !== null && $r['match_method'] !== 'staffing_gap') {
                $existingStaff[] = [
                    'id' => (int) $r['id'],
                    'staff_id' => (int) $r['staff_id'],
                    'staff_name' => $r['current_staff_name'] ?: $r['staff_name_snapshot'],
                    'current_role' => $r['current_staff_role'] ?: $r['staff_role_snapshot'],
                    'recommended_campaign_role' => $r['required_role'],
                    'required_qty' => $r['required_qty'],
                    'selected_qty' => $r['selected_qty'],
                    'deployment_location' => $r['deployment_location'],
                    'assigned_activity' => $r['assigned_activity'],
                    'match_method' => $r['match_method'],
                    'availability_status' => $r['availability_status'],
                    'conflict_status' => $r['conflict_status'],
                    'conflict_note' => $r['conflict_note'],
                    'recommendation_reason' => $r['recommendation_reason'],
                    'confirmation_status' => $r['confirmation_status'],
                    'is_confirmed' => (int) $r['is_confirmed'],
                ];
            } elseif ($r['missing_qty'] > 0 || $r['match_method'] === 'staffing_gap') {
                $missingRequirements[] = [
                    'id' => (int) $r['id'],
                    'required_role' => $r['required_role'],
                    'required_qty' => $r['required_qty'],
                    'existing_matched_qty' => $r['matched_qty'],
                    'missing_qty' => $r['missing_qty'],
                    'recommended_campaign_responsibility' => $r['assigned_activity'],
                    'deployment_location' => $r['deployment_location'],
                    'required_capability' => $r['required_capability'],
                    'reason_needed' => $r['recommendation_reason'],
                    'priority' => $r['staffing_priority'],
                    'suggested_source' => $r['staffing_source'],
                    'status' => $r['confirmation_status'],
                ];
            }
        }
        unset($r);

        $totalRequired = 0;
        $matchedExisting = 0;
        $missing = 0;
        $rolesWithShortages = 0;
        foreach ($roleSummary as $role) {
            $totalRequired += (int) $role['required_qty'];
            $matchedExisting += (int) $role['matched_qty'];
            $missing += (int) $role['missing_qty'];
            if ((int) $role['missing_qty'] > 0) {
                $rolesWithShortages++;
            }
        }

        $staffing = [
            'summary' => [
                'total_required' => $totalRequired,
                'existing_matched' => $matchedExisting,
                'missing' => $missing,
                'confirmed' => $totalConfirmed,
                'roles_with_shortages' => $rolesWithShortages,
                'conflicts' => $totalConflicts,
            ],
            'existing_staff' => $existingStaff,
            'missing_staff_requirements' => $missingRequirements,
        ];

        return [
            'participants' => $rows,
            'existing' => $existingStaff,
            'gaps' => $missingRequirements,
            'summary' => $staffing['summary'],
            'staffing' => $staffing,
            'total' => count($rows),
            'confirmed' => $totalConfirmed,
            'unmatched' => $totalUnmatched,
            'conflicts' => $totalConflicts,
        ];
    }

    public function getMatchedPartners(int $recommendationId): array
    {
        $partnerStatusExpr = $this->hasColumn('campaign_department_partners', 'status') ? 'pr.status' : "'active'";
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.partner_id, p.partner_name_snapshot, p.organization_type_snapshot,
                   p.capability_match_basis, p.capability_is_inferred, p.recommended_role,
                   p.recommendation_reason, p.is_confirmed, p.confirmed_by, p.confirmed_at,
                   pr.name AS current_partner_name, pr.organization_type AS current_org_type,
                   {$partnerStatusExpr} AS partner_status
            FROM campaign_ai_recommendation_partners p
            LEFT JOIN campaign_department_partners pr ON pr.id = p.partner_id
            WHERE p.recommendation_id = ?
            ORDER BY p.capability_is_inferred, p.partner_name_snapshot
        ");
        $stmt->execute([$recommendationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPartnerSuggestions(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.id, s.organization_type, s.capability_description, s.rationale,
                   s.expected_contribution, s.search_criteria, s.acquisition_priority,
                   s.proposed_onboarding_date, s.proposal_status, s.reviewed_by, s.reviewed_at,
                   s.created_partner_id,
                   pr.name AS created_partner_name
            FROM campaign_ai_recommendation_partner_suggestions s
            LEFT JOIN campaign_department_partners pr ON pr.id = s.created_partner_id
            WHERE s.recommendation_id = ?
            ORDER BY FIELD(s.acquisition_priority, 'high', 'medium', 'low'), s.id
        ");
        $stmt->execute([$recommendationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSchedulePhases(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, sprint_number, sprint_title, start_date, end_date,
                   duration_days, objectives, activities, assigned_staff,
                   assigned_partners, locations, phase_budget, outputs,
                   completion_criteria, dependencies, status, sort_order
            FROM campaign_ai_recommendation_schedule_phases
            WHERE recommendation_id = ?
            ORDER BY sort_order
        ");
        $stmt->execute([$recommendationId]);
        $phases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($phases as &$p) {
            $p['activities'] = $this->decodeJson($p['activities']);
            $p['assigned_staff'] = $this->decodeJson($p['assigned_staff']);
            $p['assigned_partners'] = $this->decodeJson($p['assigned_partners']);
            $p['dependencies'] = $this->decodeJson($p['dependencies']);
        }
        unset($p);

        $totalBudget = '0';
        foreach ($phases as $p) {
            $totalBudget = bcadd($totalBudget, $p['phase_budget'], 2);
        }

        return [
            'phases' => $phases,
            'total_phases' => count($phases),
            'total_budget' => $totalBudget,
        ];
    }

    public function getEventAndSeminarRecommendations(int $recommendationId): array
    {
        $summary = $this->getSummary($recommendationId);
        if (!$summary) {
            return [
                'summary' => [
                    'existing_linked_events' => 0,
                    'related_existing_events' => 0,
                    'ai_recommended_events' => 0,
                    'table_available' => false,
                ],
                'existing_events' => [],
                'related_events' => [],
                'recommended_events' => [],
                'note' => 'Recommendation not found.',
            ];
        }

        $existingEvents = $this->getExistingEventsForRecommendation($summary, true);
        $relatedEvents = $this->getExistingEventsForRecommendation($summary, false);
        $recommendedEvents = $this->buildRecommendedEventsForCampaign($summary, $existingEvents, $relatedEvents);

        return [
            'summary' => [
                'existing_linked_events' => count($existingEvents),
                'related_existing_events' => count($relatedEvents),
                'ai_recommended_events' => count($recommendedEvents),
                'table_available' => $this->tableExists('campaign_department_events'),
            ],
            'existing_events' => $existingEvents,
            'related_events' => $relatedEvents,
            'recommended_events' => $recommendedEvents,
            'note' => 'AI recommended events are planning proposals. When the recommendation is accepted, these events/seminars are inserted into the Events & Seminars table and linked to the approved campaign.',
        ];
    }

    public function getBudgetAnalysis(int $recommendationId): array
    {
        $summary = $this->getSummary($recommendationId) ?? [];
        $budget = $this->getBudgetItems($recommendationId);
        $items = $budget['items'] ?? [];
        $staff = $this->getStaffParticipants($recommendationId);
        $partners = $this->getMatchedPartners($recommendationId);
        $suggestions = $this->getPartnerSuggestions($recommendationId);
        $allocation = $this->getGovernmentAllocationOverview((int) ($summary['converted_campaign_id'] ?? 0) ?: null);

        $lineItems = [];
        foreach ($items as $index => $item) {
            $lineItems[] = [
                'number' => $index + 1,
                'category' => $item['category'] ?: 'Uncategorized',
                'item_name' => $item['item_name'],
                'description' => $item['description'],
                'related_action' => $item['related_action'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit_label'] ?: ($item['item_type'] ?: 'unit'),
                'unit_cost' => $item['unit_cost'],
                'sessions_or_days' => $item['sessions_or_days'],
                'subtotal' => $item['subtotal'],
                'pricing_source' => $item['pricing_source'] ?: 'system_estimate',
                'pricing_confidence' => $item['pricing_confidence'] ?: 'medium',
                'estimate_status' => ((int) ($item['is_estimate'] ?? 1) === 1) ? 'Estimated Only' : 'Verified',
                'validation_status' => $item['validation_status'] ?: 'estimated',
                'reason' => $item['recommendation_reason'],
                'calculation_basis' => $item['calculation_basis'] ?: 'Subtotal = quantity x unit cost x sessions/days.',
            ];
        }

        $totalEstimated = (string) ($summary['estimated_budget'] ?? $budget['total_estimated'] ?? '0.00');
        if (bccomp($totalEstimated, '0', 2) === 0 && bccomp((string) ($budget['total_estimated'] ?? '0'), '0', 2) > 0) {
            $totalEstimated = (string) $budget['total_estimated'];
        }
        $finalRecommended = (string) ($summary['final_recommended_budget'] ?? '0.00');
        $systemMax = $summary['system_budget_max'] ?? null;
        $approvedBudget = $summary['approved_budget'] ?? null;
        $budgetGap = bccomp($totalEstimated, $finalRecommended, 2) > 0 ? bcsub($totalEstimated, $finalRecommended, 2) : '0.00';
        $safeAvailable = $allocation['safe_available_budget'] ?? null;
        $allocation['proposed_ai_campaign_budget'] = bccomp($finalRecommended, '0', 2) > 0 ? $finalRecommended : null;
        if ($safeAvailable !== null && bccomp((string) $safeAvailable, '0', 2) > 0 && bccomp($finalRecommended, '0', 2) > 0) {
            $forecast = bcsub((string) $safeAvailable, $finalRecommended, 2);
            $allocation['forecast_balance_after_campaign'] = bccomp($forecast, '0', 2) > 0 ? $forecast : '0.00';
        }
        $utilization = null;
        if ($safeAvailable !== null && bccomp((string) $safeAvailable, '0', 2) > 0 && bccomp($finalRecommended, '0', 2) > 0) {
            $utilization = round(((float) $finalRecommended / (float) $safeAvailable) * 100, 2);
        }

        $categoryBreakdown = $this->buildCategoryBreakdown($items, $budget['total_estimated'] ?? '0.00');
        $actionBreakdown = $this->buildActionBreakdown($items);
        $locationBreakdown = $this->buildLocationBreakdown($summary, $items, $staff);
        $staffCostImpact = $this->buildStaffCostImpact($summary, $staff, $items);
        $partnerImpact = $this->buildPartnerImpact($partners, $suggestions, $finalRecommended);
        $contingency = $this->buildContingency($items, $budget['total_estimated'] ?? '0.00');
        $warnings = $this->buildBudgetWarnings($summary, $allocation, $items, $staff, $partnerImpact, $totalEstimated, $finalRecommended, $systemMax);

        return [
            'allocation' => $allocation,
            'campaign_summary' => [
                'estimated_campaign_need' => $totalEstimated,
                'system_maximum_allowed' => $systemMax,
                'final_recommended_budget' => $summary['final_recommended_budget'],
                'approved_budget' => $approvedBudget,
                'budget_gap' => $budgetGap,
                'budget_utilization_percentage' => $utilization,
                'validation_status' => $summary['budget_validation_status'] ?? 'unchecked',
                'pricing_confidence' => $this->overallPricingConfidence($lineItems),
                'last_calculated_date' => $summary['planning_generated_at'] ?? $summary['created_at'] ?? null,
                'approval_status' => $summary['approval_status'] ?? 'recommended',
            ],
            'warnings' => $warnings,
            'line_items' => $lineItems,
            'category_breakdown' => $categoryBreakdown,
            'action_breakdown' => $actionBreakdown,
            'location_breakdown' => $locationBreakdown,
            'staff_cost_impact' => $staffCostImpact,
            'partner_contribution_impact' => $partnerImpact,
            'contingency' => $contingency,
        ];
    }

    public function getReportSnapshots(int $recommendationId, ?string $sourceType = null): array
    {
        $sql = "
            SELECT id, source_type, external_report_id, source_local_id,
                   incident_title, description, category, severity,
                   incident_status, report_date, report_datetime,
                   location, barangay_or_area, source_system,
                   snapshot_version, source_updated_at, synchronized_at,
                   is_missing_from_source, snapshot_created_at
            FROM campaign_ai_report_snapshots
            WHERE recommendation_id = ?
        ";
        $params = [$recommendationId];

        if ($sourceType) {
            $sql .= " AND source_type = ?";
            $params[] = $sourceType;
        }

        $sql .= " ORDER BY source_type, report_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = ['crime' => [], 'disaster' => []];
        foreach ($rows as $r) {
            $type = $r['source_type'];
            $grouped[$type][] = $r;
        }

        return [
            'all' => $rows,
            'by_type' => $grouped,
            'crime_count' => count($grouped['crime']),
            'disaster_count' => count($grouped['disaster']),
            'total' => count($rows),
        ];
    }

    public function getAllTabData(int $recommendationId): array
    {
        $summary = $this->getSummary($recommendationId);
        if (!$summary) {
            return ['error' => 'Recommendation not found'];
        }

        return [
            'summary' => $summary,
            'budget' => $this->getBudgetItems($recommendationId),
            'budget_summary' => $this->getBudgetSummary($recommendationId),
            'budget_analysis' => $this->getBudgetAnalysis($recommendationId),
            'staff' => $this->getStaffParticipants($recommendationId),
            'partners' => $this->getMatchedPartners($recommendationId),
            'suggestions' => $this->getPartnerSuggestions($recommendationId),
            'schedule' => $this->getSchedulePhases($recommendationId),
            'reports' => $this->getReportSnapshots($recommendationId),
            'events' => $this->getEventAndSeminarRecommendations($recommendationId),
        ];
    }

    public function getGovernmentAllocationOverview(?int $excludeCampaignId = null): array
    {
        $stmt = $this->pdo->query("
            SELECT id, fiscal_year, total_allocation, status, effective_from, effective_until, notes
            FROM government_budget_allocations
            ORDER BY fiscal_year DESC
            LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'exists' => false,
                'message' => 'Budget Data Unavailable',
                'fiscal_year' => null,
                'total_government_allocation' => null,
                'verified_official_commitments' => null,
                'unresolved_potential_commitments' => null,
                'actual_spending' => null,
                'actual_spending_label' => 'Actual Spending Data Unavailable',
                'protected_reserve' => null,
                'safe_available_budget' => null,
                'forecast_balance_after_campaign' => null,
            ];
        }

        $budgetAllocation = new CampaignBudgetAllocationService($this->pdo);
        $commitments = $budgetAllocation->getVerifiedCommitments($excludeCampaignId);
        $available = $budgetAllocation->calculateAvailableBudget([
            'exists' => true,
            'total_allocation' => $row['total_allocation'],
        ], $commitments);

        return [
            'exists' => true,
            'id' => (int) $row['id'],
            'fiscal_year' => $row['fiscal_year'],
            'total_allocation' => $row['total_allocation'],
            'total_government_allocation' => $row['total_allocation'],
            'verified_official_commitments' => $commitments['data_available'] ?? true ? $commitments['total_committed'] : null,
            'commitment_data_available' => $commitments['data_available'] ?? true,
            'unresolved_potential_commitments' => empty($commitments['anomalies']) ? '0.00' : null,
            'actual_spending' => null,
            'actual_spending_label' => 'Actual Spending Data Unavailable',
            'protected_reserve' => $available['reserve_amount'] ?? null,
            'reserve_percent' => $available['reserve_percent'] ?? null,
            'safe_available_budget' => $available['safe_available'] ?? null,
            'forecast_balance_after_campaign' => $available['safe_available'] ?? null,
            'is_over_committed' => $available['is_over_committed'] ?? false,
            'status' => $row['status'],
            'effective_from' => $row['effective_from'],
            'effective_until' => $row['effective_until'],
            'notes' => $row['notes'],
        ];
    }

    private function buildCategoryBreakdown(array $items, string $total): array
    {
        $groups = [];
        foreach ($items as $item) {
            $category = $item['category'] ?: 'Uncategorized';
            if (!isset($groups[$category])) {
                $groups[$category] = ['category' => $category, 'total' => '0.00', 'item_count' => 0];
            }
            $groups[$category]['total'] = bcadd($groups[$category]['total'], (string) ($item['subtotal'] ?? '0'), 2);
            $groups[$category]['item_count']++;
        }

        foreach ($groups as &$group) {
            $group['percentage_of_total'] = bccomp($total, '0', 2) > 0
                ? round(((float) $group['total'] / (float) $total) * 100, 2)
                : null;
        }
        unset($group);

        usort($groups, static fn(array $a, array $b): int => bccomp($b['total'], $a['total'], 2));
        return array_values($groups);
    }

    private function buildActionBreakdown(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $action = trim((string) ($item['related_action'] ?? 'Unmapped campaign action'));
            if ($action === '') {
                $action = 'Unmapped campaign action';
            }
            if (!isset($groups[$action])) {
                $groups[$action] = ['action' => $action, 'items' => [], 'action_budget_total' => '0.00'];
            }
            $groups[$action]['items'][] = [
                'item_name' => $item['item_name'],
                'category' => $item['category'],
                'subtotal' => $item['subtotal'],
            ];
            $groups[$action]['action_budget_total'] = bcadd($groups[$action]['action_budget_total'], (string) ($item['subtotal'] ?? '0'), 2);
        }

        return array_values($groups);
    }

    private function buildLocationBreakdown(array $summary, array $items, array $staff): array
    {
        $locations = $this->decodeJson($summary['affected_locations'] ?? null);
        if (!is_array($locations) || empty($locations)) {
            $locations = ['Primary affected location'];
        }

        $materialTotal = $this->sumCategories($items, ['material', 'educational', 'safety']);
        $transportTotal = $this->sumCategories($items, ['transport']);
        $grandTotal = (string) ($this->sumAllItems($items));
        $otherTotal = bcsub(bcsub($grandTotal, $materialTotal, 2), $transportTotal, 2);
        if (bccomp($otherTotal, '0', 2) < 0) {
            $otherTotal = '0.00';
        }

        $actions = $this->decodeJson($summary['ai_recommended_actions'] ?? null);
        $activityCount = is_array($actions) ? max(1, count($actions)) : 1;
        $totalStaffRequired = max(1, (int) ($staff['summary']['total_required'] ?? 1));
        $weights = [];
        foreach (array_values($locations) as $index => $location) {
            $label = is_scalar($location) ? (string) $location : (string) ($location['name'] ?? $location['location'] ?? 'Location ' . ($index + 1));
            $weights[$label] = 1 + (($index % 3) * 0.15) + min(0.25, strlen($label) / 120);
        }
        $totalWeight = array_sum($weights) ?: 1;

        $rows = [];
        $index = 0;
        foreach ($weights as $location => $weight) {
            $share = $weight / $totalWeight;
            $rows[] = [
                'location' => $location,
                'activities' => max(1, (int) ceil(($activityCount * $share))),
                'staff_qty' => max(1, (int) ceil($totalStaffRequired * $share)),
                'material_allocation' => $this->moneyFromFloat((float) $materialTotal * $share),
                'transportation_cost' => $this->moneyFromFloat((float) $transportTotal * $share),
                'other_cost' => $this->moneyFromFloat((float) $otherTotal * $share),
                'total_estimated_cost' => $this->moneyFromFloat((float) $grandTotal * $share),
                'basis' => 'Estimated Distribution',
            ];
            $index++;
        }

        return $rows;
    }

    private function buildStaffCostImpact(array $summary, array $staff, array $items): array
    {
        $duration = max(1, (int) ($summary['recommended_duration'] ?? 30));
        $deploymentDays = max(1, min(14, (int) ceil($duration / 7)));
        $supportTotal = $this->sumCategories($items, ['staff deployment support', 'transportation']);
        $roles = [];
        foreach (($staff['participants'] ?? []) as $row) {
            $role = $row['required_role'] ?: ($row['staff_role_snapshot'] ?? 'Campaign Staff');
            if (!isset($roles[$role])) {
                $roles[$role] = [
                    'staff_role' => $role,
                    'required_qty' => (int) ($row['required_qty'] ?? 1),
                    'existing_matched_qty' => (int) ($row['matched_qty'] ?? 0),
                    'missing_qty' => (int) ($row['missing_qty'] ?? 0),
                    'deployment_days' => $deploymentDays,
                ];
            } else {
                $roles[$role]['required_qty'] = max($roles[$role]['required_qty'], (int) ($row['required_qty'] ?? 1));
                $roles[$role]['existing_matched_qty'] = max($roles[$role]['existing_matched_qty'], (int) ($row['matched_qty'] ?? 0));
                $roles[$role]['missing_qty'] = max($roles[$role]['missing_qty'], (int) ($row['missing_qty'] ?? 0));
            }
        }

        $totalPersonDays = 0;
        foreach ($roles as $role) {
            $totalPersonDays += max(1, $role['required_qty']) * $deploymentDays;
        }
        $totalPersonDays = max(1, $totalPersonDays);

        foreach ($roles as &$role) {
            $share = ($role['required_qty'] * $deploymentDays) / $totalPersonDays;
            $role['estimated_support_cost'] = $this->moneyFromFloat((float) $supportTotal * $share);
            $role['cost_type'] = $role['missing_qty'] > 0
                ? 'Transportation support / external staffing requirement'
                : 'No direct salary cost; field support estimate';
        }
        unset($role);

        return array_values($roles);
    }

    private function buildPartnerImpact(array $partners, array $suggestions, string $finalRecommended): array
    {
        $items = [];
        $confirmedImpact = '0.00';

        foreach ($partners as $partner) {
            $confirmed = (int) ($partner['is_confirmed'] ?? 0) === 1;
            $items[] = [
                'partner' => $partner['current_partner_name'] ?: $partner['partner_name_snapshot'],
                'recommended_contribution' => $partner['recommended_role'] ?: $partner['capability_match_basis'],
                'contribution_type' => $partner['organization_type_snapshot'] ?: 'Partner support',
                'estimated_budget_impact' => $confirmed ? 'Confirmed non-cash support; no monetary deduction recorded' : 'Potential cost reduction',
                'verification_status' => $confirmed ? 'Confirmed' : 'Requires Confirmation',
            ];
        }

        foreach ($suggestions as $suggestion) {
            $items[] = [
                'partner' => $suggestion['organization_type'],
                'recommended_contribution' => $suggestion['expected_contribution'] ?: $suggestion['capability_description'],
                'contribution_type' => 'Suggested partner capability',
                'estimated_budget_impact' => 'Potential support only; not deducted',
                'verification_status' => 'Requires Confirmation',
            ];
        }

        return [
            'budget_before_confirmed_partner_contributions' => $finalRecommended,
            'budget_after_confirmed_partner_contributions' => bcsub($finalRecommended, $confirmedImpact, 2),
            'items' => $items,
            'note' => 'Unconfirmed partner support is not subtracted from the official recommended budget.',
        ];
    }

    private function buildContingency(array $items, string $total): array
    {
        $contingencyAmount = $this->sumCategories($items, ['contingency']);
        $percentage = bccomp($total, '0', 2) > 0 ? round(((float) $contingencyAmount / (float) $total) * 100, 2) : 0;

        return [
            'contingency_percentage' => $percentage,
            'contingency_amount' => $contingencyAmount,
            'reason' => $percentage > 0 ? 'Included for field price changes and minor campaign scope changes.' : 'No contingency line item generated yet.',
            'may_cover' => [
                'Price changes',
                'Additional transportation',
                'Additional printing',
                'Weather-related rescheduling',
                'Emergency supplies',
            ],
        ];
    }

    private function buildBudgetWarnings(array $summary, array $allocation, array $items, array $staff, array $partnerImpact, string $estimated, string $final, mixed $systemMax): array
    {
        $warnings = [];
        if (empty($allocation['exists'])) {
            $warnings[] = ['status' => 'Budget Data Unavailable', 'message' => 'Government allocation unavailable; estimated campaign need is still shown for planning.', 'severity' => 'warning'];
        }
        if (($allocation['commitment_data_available'] ?? true) === false) {
            $warnings[] = ['status' => 'Data Quality Warning', 'message' => 'Verified official commitments cannot be read because campaign_budgets data is unavailable.', 'severity' => 'warning'];
        }
        if ($systemMax !== null && bccomp((string) $systemMax, '0', 2) > 0 && bccomp($estimated, (string) $systemMax, 2) > 0) {
            $warnings[] = ['status' => 'Insufficient Budget', 'message' => 'Estimated campaign need exceeds the system maximum allowed budget.', 'severity' => 'danger'];
        }
        foreach ($items as $item) {
            if (empty($item['pricing_source']) || ($item['pricing_confidence'] ?? '') === 'low') {
                $warnings[] = ['status' => 'Estimated Only', 'message' => 'Some budget items use estimated or low-confidence pricing sources.', 'severity' => 'info'];
                break;
            }
        }
        if ((int) ($staff['summary']['missing'] ?? 0) > 0) {
            $warnings[] = ['status' => 'Data Quality Warning', 'message' => 'Staffing shortage may increase final campaign support cost after review.', 'severity' => 'warning'];
        }
        if (!empty($partnerImpact['items'])) {
            $warnings[] = ['status' => 'Estimated Only', 'message' => 'Partner contribution impact requires confirmation and is not deducted from the official budget.', 'severity' => 'info'];
        }
        if (($summary['approval_status'] ?? '') === 'approved') {
            $warnings[] = ['status' => 'Approved', 'message' => 'Approved budget is preserved and is not overwritten by recalculation.', 'severity' => 'success'];
        }
        if (bccomp($final, '0', 2) === 0 && bccomp($estimated, '0', 2) > 0) {
            $warnings[] = ['status' => 'Not Calculated', 'message' => 'Final recommended budget is not available yet; line items show estimated need only.', 'severity' => 'warning'];
        }

        return $warnings;
    }

    private function getExistingEventsForRecommendation(array $summary, bool $linkedOnly): array
    {
        if (!$this->tableExists('campaign_department_events')) {
            return [];
        }

        $titleExpr = $this->coalesceExistingColumns('e', [
            'event_title',
            'event_name',
            'name',
        ], "'Untitled Event'");
        $descriptionExpr = $this->coalesceExistingColumns('e', [
            'event_description',
            'description',
        ], "NULL");
        $eventTypeExpr = $this->hasColumn('campaign_department_events', 'event_type') ? 'e.event_type' : "'seminar'";
        $dateExpr = $this->coalesceExistingColumns('e', [
            'date',
            'event_date',
            'starts_at',
        ], "NULL");
        $startTimeExpr = $this->coalesceExistingColumns('e', [
            'start_time',
            'event_time',
        ], "NULL");
        if ($this->hasColumn('campaign_department_events', 'starts_at')) {
            $startTimeExpr = "COALESCE({$startTimeExpr}, TIME(e.starts_at))";
        }
        $endTimeExpr = $this->coalesceExistingColumns('e', [
            'end_time',
            'ends_at',
        ], "NULL");
        if ($this->hasColumn('campaign_department_events', 'ends_at')) {
            $endTimeExpr = "COALESCE({$endTimeExpr}, TIME(e.ends_at))";
        }
        $statusExpr = $this->coalesceExistingColumns('e', [
            'event_status',
            'status',
        ], "'scheduled'");
        $campaignExpr = $this->coalesceExistingColumns('e', [
            'linked_campaign_id',
            'campaign_id',
        ], "NULL");
        $hazardExpr = $this->hasColumn('campaign_department_events', 'hazard_focus') ? 'e.hazard_focus' : 'NULL';
        $venueExpr = $this->hasColumn('campaign_department_events', 'venue') ? 'e.venue' : 'NULL';
        $locationExpr = $this->hasColumn('campaign_department_events', 'location') ? 'e.location' : 'NULL';
        $capacityExpr = $this->hasColumn('campaign_department_events', 'capacity') ? 'e.capacity' : 'NULL';
        $attendanceExpr = $this->hasColumn('campaign_department_events', 'attendance_count') ? 'e.attendance_count' : '0';
        $createdExpr = $this->hasColumn('campaign_department_events', 'created_at') ? 'e.created_at' : 'NULL';

        $sql = "
            SELECT e.id,
                   {$titleExpr} AS event_title,
                   {$eventTypeExpr} AS event_type,
                   {$descriptionExpr} AS event_description,
                   {$hazardExpr} AS hazard_focus,
                   {$dateExpr} AS event_date,
                   {$startTimeExpr} AS start_time,
                   {$endTimeExpr} AS end_time,
                   {$venueExpr} AS venue,
                   {$locationExpr} AS location,
                   {$statusExpr} AS event_status,
                   {$campaignExpr} AS linked_campaign_id,
                   {$capacityExpr} AS capacity,
                   {$attendanceExpr} AS attendance_count,
                   {$createdExpr} AS created_at
            FROM campaign_department_events e
        ";

        $params = [];
        $where = [];
        if ($linkedOnly) {
            $campaignId = (int) ($summary['converted_campaign_id'] ?? 0);
            if ($campaignId <= 0) {
                return [];
            }
            $where[] = "{$campaignExpr} = ?";
            $params[] = $campaignId;
        } else {
            $terms = $this->eventSearchTerms($summary);
            $likeParts = [];
            foreach ($terms as $term) {
                $likeParts[] = "LOWER(CONCAT_WS(' ', {$titleExpr}, {$eventTypeExpr}, {$descriptionExpr}, {$hazardExpr}, {$venueExpr}, {$locationExpr})) LIKE ?";
                $params[] = '%' . mb_strtolower($term) . '%';
            }
            if (!empty($likeParts)) {
                $where[] = '(' . implode(' OR ', $likeParts) . ')';
            }
            $campaignId = (int) ($summary['converted_campaign_id'] ?? 0);
            if ($campaignId > 0) {
                $where[] = "({$campaignExpr} IS NULL OR {$campaignExpr} != ?)";
                $params[] = $campaignId;
            }
        }

        $where[] = "LOWER(COALESCE({$statusExpr}, '')) != 'archived'";
        $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY {$dateExpr} DESC, {$startTimeExpr} DESC, e.id DESC LIMIT " . ($linkedOnly ? '20' : '12');

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('AI recommendation event lookup failed: ' . $e->getMessage());
            return [];
        }

        return array_map(fn(array $row): array => $this->normalizeExistingEventRow($row, $summary), $rows);
    }

    private function normalizeExistingEventRow(array $row, array $summary): array
    {
        $score = $this->scoreExistingEventMatch($row, $summary);
        return [
            'event_id' => (int) $row['id'],
            'event_title' => $row['event_title'],
            'event_type' => $row['event_type'] ?: 'seminar',
            'event_description' => $row['event_description'],
            'hazard_focus' => $row['hazard_focus'],
            'event_date' => $this->dateOnly($row['event_date'] ?? null),
            'start_time' => $this->timeOnly($row['start_time'] ?? null),
            'end_time' => $this->timeOnly($row['end_time'] ?? null),
            'venue' => $row['venue'],
            'location' => $row['location'],
            'event_status' => $row['event_status'],
            'linked_campaign_id' => $row['linked_campaign_id'] === null ? null : (int) $row['linked_campaign_id'],
            'capacity' => $row['capacity'] === null ? null : (int) $row['capacity'],
            'attendance_count' => (int) ($row['attendance_count'] ?? 0),
            'match_score' => $score,
            'match_basis' => $score >= 80 ? 'Linked campaign event' : ($score >= 45 ? 'Related event content match' : 'Event table record'),
        ];
    }

    private function buildRecommendedEventsForCampaign(array $summary, array $existingEvents, array $relatedEvents): array
    {
        $actions = $this->normalList($summary['ai_recommended_actions'] ?? []);
        $locations = $this->normalList($summary['affected_locations'] ?? []);
        $audience = trim((string) ($summary['ai_target_audience'] ?? 'Community residents and stakeholders'));
        $duration = max(7, (int) ($summary['recommended_duration'] ?? 30));
        $priority = strtolower((string) ($summary['priority_level'] ?? 'medium'));
        $categoryText = mb_strtolower(implode(' ', array_filter([
            $summary['category'] ?? '',
            $summary['incident_category'] ?? '',
            $summary['main_trend'] ?? '',
            $summary['campaign_title'] ?? '',
            implode(' ', $actions),
        ])));
        $audienceText = mb_strtolower($audience);
        $isYouthCampaign = (bool) preg_match('/youth|student|school|teen|adolescent|kabataan|sangguniang kabataan/', $categoryText . ' ' . $audienceText);

        if (empty($actions)) {
            $actions = [
                'Orient the community about the campaign and the current public safety trend.',
                'Run a skills-based safety session for priority residents and stakeholders.',
                'Evaluate learnings and document next actions after field implementation.',
            ];
        }
        if (empty($locations)) {
            $locations = ['Primary affected location'];
        }

        $baseDate = $this->eventPlanningStartDate($summary);
        $templates = [
            [
                'event_type' => 'orientation',
                'title' => $isYouthCampaign ? 'Youth Safety Campaign Kickoff and Orientation' : 'Campaign Kickoff and Public Safety Orientation',
                'objective' => $isYouthCampaign ? 'Introduce the youth-focused campaign, explain the safety evidence, and align students, parents, schools, SK leaders, and barangay partners on prevention actions.' : 'Introduce the recommended campaign, explain the incident trend, and align residents on prevention actions.',
                'action_index' => 0,
                'offset_days' => 2,
                'time' => '09:00',
                'duration_hours' => 2,
                'priority' => 'high',
            ],
            [
                'event_type' => 'seminar',
                'title' => $isYouthCampaign ? 'Youth Safety, Leadership and Prevention Seminar' : $this->topicTitle($summary, 'Community Prevention Seminar'),
                'objective' => $isYouthCampaign ? 'Teach youth-friendly prevention, safe reporting, peer leadership, digital safety, and referral steps based on the supporting reports and partner data.' : 'Teach practical prevention, reporting, and referral steps based on the supporting reports.',
                'action_index' => min(1, count($actions) - 1),
                'offset_days' => max(5, (int) floor($duration * 0.25)),
                'time' => '14:00',
                'duration_hours' => 3,
                'priority' => in_array($priority, ['high', 'critical'], true) ? 'high' : 'medium',
            ],
            [
                'event_type' => preg_match('/disaster|fire|earthquake|flood|storm|evacuat|emergency/', $categoryText) ? 'drill' : 'workshop',
                'title' => preg_match('/disaster|fire|earthquake|flood|storm|evacuat|emergency/', $categoryText)
                    ? 'Preparedness Drill and Response Simulation'
                    : ($isYouthCampaign ? 'Youth Safety Skills and Positive Engagement Workshop' : 'Hands-on Community Safety Workshop'),
                'objective' => 'Practice the recommended campaign behavior with staff, partners, and residents before wider rollout.',
                'action_index' => min(2, count($actions) - 1),
                'offset_days' => max(8, (int) floor($duration * 0.5)),
                'time' => '09:00',
                'duration_hours' => 3,
                'priority' => 'medium',
            ],
            [
                'event_type' => 'seminar',
                'title' => $isYouthCampaign ? 'School, SK and Partner Coordination Seminar' : 'Partner and Volunteer Coordination Seminar',
                'objective' => $isYouthCampaign ? 'Coordinate school, SK, parent, volunteer, referral, and barangay safety roles for youth-focused activities.' : 'Coordinate partner roles, volunteer support, referral flow, materials, and field deployment responsibilities.',
                'action_index' => min(3, count($actions) - 1),
                'offset_days' => max(10, (int) floor($duration * 0.65)),
                'time' => '13:30',
                'duration_hours' => 2,
                'priority' => 'medium',
            ],
            [
                'event_type' => 'workshop',
                'title' => 'Post-Campaign Learning and Improvement Workshop',
                'objective' => 'Review attendance, incident feedback, staff notes, and next-step recommendations after campaign activities.',
                'action_index' => count($actions) - 1,
                'offset_days' => max(14, min($duration, $duration - 2)),
                'time' => '10:00',
                'duration_hours' => 2,
                'priority' => 'low',
            ],
        ];

        $eventCount = count($locations) >= 5 || in_array($priority, ['high', 'critical'], true) ? 5 : 4;
        $templates = array_slice($templates, 0, $eventCount);
        $recommendations = [];
        foreach ($templates as $index => $template) {
            $location = $locations[$index % count($locations)] ?: 'Primary affected location';
            $date = (clone $baseDate)->modify('+' . (int) $template['offset_days'] . ' days');
            $start = $template['time'];
            $end = date('H:i', strtotime($start . ' +' . (int) $template['duration_hours'] . ' hours'));
            $action = $actions[$template['action_index']] ?? $actions[0];
            $hazardFocus = $summary['main_trend'] ?? $summary['incident_category'] ?? $summary['category'] ?? 'Public safety';

            $recommendations[] = [
                'sequence' => $index + 1,
                'event_title' => $template['title'],
                'event_type' => $template['event_type'],
                'objective' => $template['objective'],
                'recommended_campaign_action' => $action,
                'hazard_focus' => $hazardFocus,
                'target_audience' => $audience,
                'recommended_date' => $date->format('Y-m-d'),
                'start_time' => $start,
                'end_time' => $end,
                'duration_hours' => (int) $template['duration_hours'],
                'recommended_venue_or_location' => $location,
                'trainer_requirements' => $this->trainerRequirementForEvent($template['event_type'], $categoryText),
                'equipment_requirements' => $this->equipmentRequirementForEvent($template['event_type'], $categoryText),
                'volunteer_requirements' => $this->volunteerRequirementForEvent($locations, $priority),
                'expected_output' => $this->expectedOutputForEvent($template['event_type']),
                'priority' => $template['priority'],
                'recommendation_reason' => $this->eventRecommendationReason($summary, $template, $existingEvents, $relatedEvents),
                'status' => 'For Review',
                'action' => 'Review in Events & Seminars',
            ];
        }

        return $recommendations;
    }

    private function eventSearchTerms(array $summary): array
    {
        $terms = [];
        foreach ([
            $summary['campaign_title'] ?? '',
            $summary['main_trend'] ?? '',
            $summary['incident_category'] ?? '',
            $summary['category'] ?? '',
        ] as $term) {
            $term = trim((string) $term);
            if ($term !== '') {
                $terms[] = $term;
            }
        }
        foreach ($this->normalList($summary['affected_locations'] ?? []) as $location) {
            if ($location !== '') {
                $terms[] = $location;
            }
        }
        return array_slice(array_values(array_unique($terms)), 0, 8);
    }

    private function scoreExistingEventMatch(array $row, array $summary): int
    {
        $score = 0;
        $campaignId = (int) ($summary['converted_campaign_id'] ?? 0);
        if ($campaignId > 0 && (int) ($row['linked_campaign_id'] ?? 0) === $campaignId) {
            $score += 80;
        }
        $haystack = mb_strtolower(implode(' ', array_filter([
            $row['event_title'] ?? '',
            $row['event_type'] ?? '',
            $row['event_description'] ?? '',
            $row['hazard_focus'] ?? '',
            $row['venue'] ?? '',
            $row['location'] ?? '',
        ])));
        foreach ($this->eventSearchTerms($summary) as $term) {
            if ($term !== '' && str_contains($haystack, mb_strtolower($term))) {
                $score += 10;
            }
        }
        return min(100, $score);
    }

    private function eventPlanningStartDate(array $summary): \DateTimeImmutable
    {
        foreach (['effective_start_date', 'earliest_date', 'created_at'] as $field) {
            if (!empty($summary[$field])) {
                $date = strtotime((string) $summary[$field]);
                if ($date) {
                    $candidate = new \DateTimeImmutable(date('Y-m-d', $date));
                    $today = new \DateTimeImmutable(date('Y-m-d'));
                    return $candidate < $today ? $today : $candidate;
                }
            }
        }
        return new \DateTimeImmutable(date('Y-m-d'));
    }

    private function topicTitle(array $summary, string $suffix): string
    {
        $topic = trim((string) ($summary['main_trend'] ?? $summary['incident_category'] ?? 'Public Safety'));
        $topic = preg_replace('/\s+/', ' ', $topic) ?: 'Public Safety';
        return $topic . ' ' . $suffix;
    }

    private function trainerRequirementForEvent(string $eventType, string $categoryText): string
    {
        if (preg_match('/youth|student|school|teen|adolescent|kabataan|sangguniang kabataan/', $categoryText)) {
            return 'SK/youth development facilitator, school representative or guidance counselor, barangay safety officer, documentation staff';
        }
        if (str_contains($categoryText, 'fire')) {
            return 'BFP/fire safety resource person, barangay safety officer, documentation staff';
        }
        if (preg_match('/disaster|earthquake|flood|storm|emergency|evacuat/', $categoryText)) {
            return 'DRRM officer, emergency response facilitator, barangay safety officer';
        }
        if (preg_match('/crime|theft|robbery|assault|violence|drug|security/', $categoryText)) {
            return 'Peace and order officer, barangay tanod lead, community policing partner';
        }
        if ($eventType === 'workshop') {
            return 'Campaign coordinator, subject matter facilitator, documentation staff';
        }
        return 'Campaign lead, public safety facilitator, documentation staff';
    }

    private function equipmentRequirementForEvent(string $eventType, string $categoryText): string
    {
        $items = ['attendance sheets', 'printed handouts', 'sound system', 'presentation device'];
        if ($eventType === 'drill') {
            $items[] = 'scenario cards';
            $items[] = 'safety markers';
            $items[] = 'first-aid kit';
        }
        if (preg_match('/crime|theft|robbery|security/', $categoryText)) {
            $items[] = 'incident reporting flowchart';
            $items[] = 'hotline directory';
        }
        return implode(', ', array_unique($items));
    }

    private function volunteerRequirementForEvent(array $locations, string $priority): string
    {
        $qty = max(2, min(12, count($locations) * (in_array($priority, ['high', 'critical'], true) ? 2 : 1)));
        return $qty . ' volunteer aides for registration, crowd guidance, materials distribution, and post-event documentation';
    }

    private function expectedOutputForEvent(string $eventType): string
    {
        return match ($eventType) {
            'orientation' => 'Aligned participants, attendance record, shared campaign timeline',
            'drill' => 'Completed practice scenario, response gaps list, follow-up safety actions',
            'workshop' => 'Participant output sheets, skills practice notes, improvement actions',
            default => 'Attendance record, learning checklist, questions and referral concerns',
        };
    }

    private function eventRecommendationReason(array $summary, array $template, array $existingEvents, array $relatedEvents): string
    {
        $reportCount = (int) ($summary['report_count'] ?? 0);
        $locationCount = count($this->normalList($summary['affected_locations'] ?? []));
        $existingNote = count($existingEvents) > 0
            ? ' Existing linked events are shown for comparison.'
            : ' No linked event is currently recorded for this AI recommendation.';
        return 'Recommended from ' . $reportCount . ' supporting report(s), ' . max(1, $locationCount) . ' affected location(s), campaign priority, and the selected campaign action. ' . $template['event_type'] . ' format fits the objective.' . $existingNote;
    }

    private function overallPricingConfidence(array $lineItems): string
    {
        if (empty($lineItems)) {
            return 'Not Calculated';
        }
        foreach ($lineItems as $item) {
            if (($item['pricing_confidence'] ?? '') === 'low') {
                return 'Low';
            }
        }
        foreach ($lineItems as $item) {
            if (($item['estimate_status'] ?? '') === 'Estimated Only') {
                return 'Medium';
            }
        }
        return 'Verified';
    }

    private function sumCategories(array $items, array $needles): string
    {
        $total = '0.00';
        foreach ($items as $item) {
            $category = mb_strtolower((string) ($item['category'] ?? ''));
            $type = mb_strtolower((string) ($item['item_type'] ?? ''));
            foreach ($needles as $needle) {
                if (str_contains($category, $needle) || str_contains($type, $needle)) {
                    $total = bcadd($total, (string) ($item['subtotal'] ?? '0'), 2);
                    break;
                }
            }
        }
        return $total;
    }

    private function sumAllItems(array $items): string
    {
        $total = '0.00';
        foreach ($items as $item) {
            $total = bcadd($total, (string) ($item['subtotal'] ?? '0'), 2);
        }
        return $total;
    }

    private function moneyFromFloat(float $value): string
    {
        return number_format(max(0, $value), 2, '.', '');
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null) return null;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $value;
        }
        return $value;
    }

    private function normalList(mixed $value): array
    {
        $decoded = $this->decodeJson($value);
        if (is_array($decoded)) {
            $items = [];
            foreach ($decoded as $item) {
                if (is_scalar($item)) {
                    $text = trim((string) $item);
                } elseif (is_array($item)) {
                    $text = trim((string) ($item['name'] ?? $item['title'] ?? $item['location'] ?? $item['barangay'] ?? ''));
                } else {
                    $text = '';
                }
                if ($text !== '') {
                    $items[] = $text;
                }
            }
            return array_values(array_unique($items));
        }

        $text = trim((string) ($decoded ?? ''));
        if ($text === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $text) ?: [])));
    }

    private function dateOnly(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $time = strtotime((string) $value);
        return $time ? date('Y-m-d', $time) : null;
    }

    private function timeOnly(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $time = strtotime((string) $value);
        return $time ? date('H:i', $time) : null;
    }

    private function coalesceExistingColumns(string $alias, array $columns, string $fallback): string
    {
        $parts = [];
        foreach ($columns as $column) {
            if ($this->hasColumn('campaign_department_events', $column)) {
                $parts[] = "{$alias}.{$column}";
            }
        }
        if (empty($parts)) {
            return $fallback;
        }
        $parts[] = $fallback;
        return 'COALESCE(' . implode(', ', $parts) . ')';
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

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
