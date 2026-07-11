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
            SELECT id, item_name, description, category, item_type,
                   quantity, unit_cost, sessions_or_days, subtotal,
                   funding_source, related_action, recommendation_reason,
                   pricing_source, is_estimate, validation_status, sort_order
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
                   p.match_method, p.recommendation_reason, p.availability_status,
                   p.conflict_status, p.conflict_note, p.is_confirmed,
                   p.confirmed_by, p.confirmed_at,
                   rs.name AS current_staff_name, rs.role AS current_staff_role
            FROM campaign_ai_recommendation_participants p
            LEFT JOIN campaign_department_reference_staff rs ON rs.id = p.staff_id
            WHERE p.recommendation_id = ?
            ORDER BY p.match_method, p.staff_role_snapshot
        ");
        $stmt->execute([$recommendationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalConfirmed = 0;
        $totalUnmatched = 0;
        $totalConflicts = 0;

        foreach ($rows as &$r) {
            if ($r['is_confirmed']) $totalConfirmed++;
            if ($r['match_method'] === 'unmatched') $totalUnmatched++;
            if ($r['conflict_status'] === 'possible_conflict') $totalConflicts++;
        }
        unset($r);

        return [
            'participants' => $rows,
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
            'staff' => $this->getStaffParticipants($recommendationId),
            'partners' => $this->getMatchedPartners($recommendationId),
            'suggestions' => $this->getPartnerSuggestions($recommendationId),
            'schedule' => $this->getSchedulePhases($recommendationId),
            'reports' => $this->getReportSnapshots($recommendationId),
        ];
    }

    public function getGovernmentAllocationOverview(): array
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
            ];
        }

        return [
            'exists' => true,
            'id' => (int) $row['id'],
            'fiscal_year' => $row['fiscal_year'],
            'total_allocation' => $row['total_allocation'],
            'status' => $row['status'],
            'effective_from' => $row['effective_from'],
            'effective_until' => $row['effective_until'],
            'notes' => $row['notes'],
        ];
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
