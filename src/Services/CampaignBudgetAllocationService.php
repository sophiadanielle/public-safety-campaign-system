<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class CampaignBudgetAllocationService
{
    private PDO $pdo;
    private BudgetValidator $budgetValidator;
    private const RESERVE_PERCENT = 20;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->budgetValidator = new BudgetValidator($pdo);
    }

    public function getGovernmentAllocation(?int $fiscalYear = null): array
    {
        if ($fiscalYear === null) {
            $fiscalYear = (int) date('Y');
        }

        $stmt = $this->pdo->prepare("
            SELECT id, fiscal_year, total_allocation, status, effective_from, effective_until, notes
            FROM government_budget_allocations
            WHERE fiscal_year = ? OR fiscal_year LIKE ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $likeYear = (string) $fiscalYear . '%';
        $stmt->execute([(string) $fiscalYear, $likeYear]);
        $allocation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$allocation) {
            return [
                'exists' => false,
                'message' => 'Budget Data Unavailable',
                'hint' => 'No government budget allocation has been entered for fiscal year ' . $fiscalYear . '. An authorized finance/admin user must enter the allocation before budget planning can proceed.',
            ];
        }

        return [
            'exists' => true,
            'id' => (int) $allocation['id'],
            'fiscal_year' => $allocation['fiscal_year'],
            'total_allocation' => $allocation['total_allocation'],
            'status' => $allocation['status'],
            'effective_from' => $allocation['effective_from'],
            'effective_until' => $allocation['effective_until'],
            'notes' => $allocation['notes'],
        ];
    }

    public function getVerifiedCommitments(?int $excludeCampaignId = null): array
    {
        if (!$this->tableExists('campaign_budgets')) {
            return [
                'total_committed' => '0.00',
                'item_count' => 0,
                'by_funding_source' => [],
                'anomalies' => [],
                'items' => [],
                'data_available' => false,
                'message' => 'Actual campaign budget commitment data unavailable',
            ];
        }

        $sql = "
            SELECT c.id AS campaign_id, c.title, cb.id AS budget_item_id,
                   cb.item_name, cb.item_type, cb.quantity, cb.unit_cost,
                   (cb.quantity * cb.unit_cost) AS line_total,
                   cb.funding_source
            FROM campaign_budgets cb
            INNER JOIN campaign_department_campaigns c ON c.id = cb.campaign_id
            WHERE cb.is_archived = 0
        ";
        $params = [];
        if ($excludeCampaignId !== null) {
            $sql .= " AND cb.campaign_id != ?";
            $params[] = $excludeCampaignId;
        }
        $sql .= " ORDER BY c.id, cb.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCommitted = '0';
        $byFundingSource = [];
        $anomalies = [];

        foreach ($items as $item) {
            $totalCommitted = bcadd($totalCommitted, $item['line_total'], 2);
            $source = $item['funding_source'] ?? 'unspecified';
            if (!isset($byFundingSource[$source])) {
                $byFundingSource[$source] = '0';
            }
            $byFundingSource[$source] = bcadd($byFundingSource[$source], $item['line_total'], 2);

            if (bccomp($item['line_total'], '1000000', 2) > 0) {
                $anomalies[] = [
                    'campaign_id' => $item['campaign_id'],
                    'campaign_title' => $item['title'],
                    'item_name' => $item['item_name'],
                    'line_total' => $item['line_total'],
                    'note' => 'Line item exceeds ₱1,000,000',
                ];
            }
        }

        return [
            'total_committed' => $totalCommitted,
            'item_count' => count($items),
            'by_funding_source' => $byFundingSource,
            'anomalies' => $anomalies,
            'items' => $items,
            'data_available' => true,
        ];
    }

    public function calculateAvailableBudget(array $allocation, array $commitments): array
    {
        if (!$allocation['exists']) {
            return [
                'available' => null,
                'reserve' => null,
                'safe_available' => null,
                'message' => 'No allocation data available',
            ];
        }

        $totalAllocation = $allocation['total_allocation'];
        $verifiedCommitments = $commitments['total_committed'];
        $grossRemaining = bcsub($totalAllocation, $verifiedCommitments, 2);

        $reservePercent = $this->getReservePercent();
        $reserveAmount = '0';
        if (bccomp($grossRemaining, '0', 2) > 0) {
            $reserveAmount = bcmul($grossRemaining, (string) ($reservePercent / 100), 4);
            $reserveAmount = bcadd($reserveAmount, '0', 2);
        }

        $safeAvailable = '0';
        if (bccomp($grossRemaining, $reserveAmount, 2) > 0) {
            $safeAvailable = bcsub($grossRemaining, $reserveAmount, 2);
        }

        return [
            'total_allocation' => $totalAllocation,
            'verified_commitments' => $verifiedCommitments,
            'gross_remaining' => $grossRemaining,
            'reserve_percent' => $reservePercent,
            'reserve_amount' => $reserveAmount,
            'safe_available' => $safeAvailable,
            'is_over_committed' => bccomp($verifiedCommitments, $totalAllocation, 2) > 0,
        ];
    }

    public function generateBudgetDistribution(
        int $recommendationId,
        int $campaignId,
        string $safeAvailable
    ): array {
        if (!$this->tableExists('campaign_budgets')) {
            return $this->generateEstimatedBudgetFromRecommendation($recommendationId, [
                'recommended_duration' => 30,
                'report_count' => 1,
                'ai_recommended_actions' => [],
                'affected_locations' => [],
            ]);
        }

        $stmt = $this->pdo->prepare("
            SELECT id, item_name, item_type, quantity, unit_cost, funding_source
            FROM campaign_budgets
            WHERE campaign_id = ? AND is_archived = 0
            ORDER BY id
        ");
        $stmt->execute([$campaignId]);
        $committedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining = $safeAvailable;
        $generatedItems = [];
        $totalDistributed = '0';

        foreach ($committedItems as $item) {
            $lineTotal = bcmul((string) $item['quantity'], (string) $item['unit_cost'], 2);

            $stmt2 = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_budget_items
                    (recommendation_id, item_name, item_type, unit_label, quantity, unit_cost, sessions_or_days,
                     subtotal, funding_source, recommendation_reason, pricing_source, pricing_confidence,
                     calculation_basis, is_estimate, validation_status, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt2->execute([
                $recommendationId,
                $item['item_name'],
                $item['item_type'],
                $item['item_type'],
                $item['quantity'],
                $item['unit_cost'],
                1,
                $lineTotal,
                $item['funding_source'] ?? 'government_allocated',
                'Committed line item carried forward from campaign budget',
                'campaign_budget',
                'verified',
                'Committed quantity x committed unit cost from campaign_budgets.',
                0,
                'unchecked',
                count($generatedItems) + 1,
            ]);

            if (bccomp($remaining, $lineTotal, 2) >= 0) {
                $remaining = bcsub($remaining, $lineTotal, 2);
            }

            $totalDistributed = bcadd($totalDistributed, $lineTotal, 2);
            $generatedItems[] = $item['item_name'];
        }

        $stmt3 = $this->pdo->prepare('
            UPDATE campaign_department_ai_recommendations
            SET estimated_budget = ?, system_budget_max = ?, final_recommended_budget = ?
            WHERE id = ?
        ');
        $stmt3->execute([$totalDistributed, $safeAvailable, $totalDistributed, $recommendationId]);

        return [
            'total_distributed' => $totalDistributed,
            'remaining_after_distribution' => $remaining,
            'items_generated' => count($generatedItems),
        ];
    }

    public function generateEstimatedBudgetFromRecommendation(int $recommendationId, array $recommendation, ?array $campaign = null): array
    {
        $this->pdo->prepare('
            DELETE FROM campaign_ai_recommendation_budget_items
            WHERE recommendation_id = ?
        ')->execute([$recommendationId]);

        $actions = $this->decodeList($recommendation['ai_recommended_actions'] ?? null);
        if (empty($actions)) {
            $actions = [
                'Prepare and distribute campaign information materials',
                'Conduct community orientation sessions',
                'Coordinate response and prevention activities with local stakeholders',
            ];
        }

        $locations = $this->decodeList($recommendation['affected_locations'] ?? null);
        $locationCount = max(1, count($locations));
        $reportCount = max(1, (int) ($recommendation['report_count'] ?? 1));
        $duration = max(7, (int) ($recommendation['recommended_duration'] ?? 30));
        $sessions = max(1, min(8, (int) ceil($reportCount / 3), $locationCount + 1));
        $audienceMultiplier = max(1, min(5, (int) ceil($reportCount / 5)));
        $deploymentDays = max(1, min(14, (int) ceil($duration / 7)));
        $priority = strtolower((string) ($recommendation['priority_level'] ?? 'medium'));
        $priorityFactor = match ($priority) {
            'critical' => 1.4,
            'high' => 1.25,
            'low' => 0.85,
            default => 1.0,
        };
        $estimatedStaffQty = max(4, min(60, 1 + $locationCount + (int) ceil($locationCount * 2 * $priorityFactor) + (int) ceil($reportCount / 8)));
        $materialsQty = max(100, $reportCount * 25, $locationCount * 100);
        $actionCount = max(1, count($actions));

        $templates = [
            [
                'item_name' => 'Educational materials and handouts',
                'description' => 'Printed prevention, preparedness, and referral materials tied to the AI recommended actions.',
                'category' => 'Educational materials',
                'item_type' => 'materials',
                'unit_label' => 'copies',
                'quantity' => (string) $materialsQty,
                'unit_cost' => '8.00',
                'sessions_or_days' => '1',
                'related_action' => $actions[0] ?? null,
                'pricing_confidence' => 'medium',
            ],
            [
                'item_name' => 'Community information sessions',
                'description' => 'Barangay-level sessions for the target audience and affected locations.',
                'category' => 'Community sessions',
                'item_type' => 'activity',
                'unit_label' => 'session',
                'quantity' => (string) $sessions,
                'unit_cost' => '2500.00',
                'sessions_or_days' => '1',
                'related_action' => $actions[1] ?? ($actions[0] ?? null),
                'pricing_confidence' => 'medium',
            ],
            [
                'item_name' => 'Communication and public advisories',
                'description' => 'SMS, online, and posted announcements for campaign reach and reminders.',
                'category' => 'Communication',
                'item_type' => 'communication',
                'unit_label' => 'weekly notice set',
                'quantity' => (string) $audienceMultiplier,
                'unit_cost' => '1200.00',
                'sessions_or_days' => (string) max(1, min(14, (int) ceil($duration / 7))),
                'related_action' => $actions[2] ?? ($actions[0] ?? null),
                'pricing_confidence' => 'medium',
            ],
            [
                'item_name' => 'Coordination and transportation support',
                'description' => 'Local coordination, mobility, and logistics support for affected areas.',
                'category' => 'Transportation',
                'item_type' => 'logistics',
                'unit_label' => 'location-day',
                'quantity' => (string) $locationCount,
                'unit_cost' => '1500.00',
                'sessions_or_days' => (string) $sessions,
                'related_action' => $actions[3] ?? ($actions[0] ?? null),
                'pricing_confidence' => 'medium',
            ],
            [
                'item_name' => 'Staff deployment support',
                'description' => 'Meal, local mobility, and field support for recommended staff deployment. This is not a salary estimate.',
                'category' => 'Staff deployment support',
                'item_type' => 'personnel_support',
                'unit_label' => 'person-day',
                'quantity' => (string) $estimatedStaffQty,
                'unit_cost' => '250.00',
                'sessions_or_days' => (string) $deploymentDays,
                'related_action' => 'Deploy recommended campaign staff across affected locations',
                'pricing_confidence' => 'low',
            ],
            [
                'item_name' => 'Venue, sound system, and field setup',
                'description' => 'Basic venue or field setup support for information sessions and campaign activities.',
                'category' => 'Venue and field setup',
                'item_type' => 'venue_setup',
                'unit_label' => 'activity site',
                'quantity' => (string) max(1, min($locationCount, $sessions)),
                'unit_cost' => '1800.00',
                'sessions_or_days' => '1',
                'related_action' => $actions[1] ?? ($actions[0] ?? null),
                'pricing_confidence' => 'low',
            ],
            [
                'item_name' => 'Safety and emergency supplies',
                'description' => 'Basic first-aid, safety, signage, and emergency readiness materials for field activities.',
                'category' => 'Safety equipment',
                'item_type' => 'supplies',
                'unit_label' => 'kit',
                'quantity' => (string) max(1, min(10, $locationCount)),
                'unit_cost' => '2200.00',
                'sessions_or_days' => '1',
                'related_action' => 'Maintain safety readiness during campaign deployment',
                'pricing_confidence' => 'medium',
            ],
            [
                'item_name' => 'Documentation, monitoring, and evaluation',
                'description' => 'Attendance, report documentation, outcome monitoring, and post-campaign review.',
                'category' => 'Monitoring and evaluation',
                'item_type' => 'documentation',
                'unit_label' => 'campaign',
                'quantity' => '1',
                'unit_cost' => (string) max(3500, min(12000, 2500 + ($actionCount * 500) + ($locationCount * 350))),
                'sessions_or_days' => '1',
                'related_action' => 'Monitor implementation and evaluate results',
                'pricing_confidence' => 'medium',
            ],
        ];

        $baseTotal = '0.00';
        foreach ($templates as $item) {
            $baseTotal = bcadd($baseTotal, bcmul(bcmul($item['quantity'], $item['unit_cost'], 2), $item['sessions_or_days'], 2), 2);
        }
        $contingencyPercent = bccomp($baseTotal, '0', 2) > 0 ? '10' : '0';
        $contingencyAmount = bcadd(bcmul($baseTotal, '0.10', 4), '0', 2);
        if (bccomp($contingencyAmount, '0', 2) > 0) {
            $templates[] = [
                'item_name' => 'Contingency for field changes',
                'description' => 'Reserve inside the AI estimate for price changes, added transport, reprinting, weather delays, or emergency supplies.',
                'category' => 'Contingency',
                'item_type' => 'contingency',
                'unit_label' => 'campaign',
                'quantity' => '1',
                'unit_cost' => $contingencyAmount,
                'sessions_or_days' => '1',
                'related_action' => 'Protect campaign implementation from small scope or price changes',
                'pricing_confidence' => 'estimated',
            ];
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_budget_items
                (recommendation_id, item_name, description, category, item_type, unit_label, quantity, unit_cost,
                 sessions_or_days, subtotal, funding_source, related_action, recommendation_reason,
                 pricing_source, pricing_confidence, calculation_basis, is_estimate, validation_status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $total = '0.00';
        foreach ($templates as $index => $item) {
            $subtotal = bcmul(bcmul($item['quantity'], $item['unit_cost'], 2), $item['sessions_or_days'], 2);
            $total = bcadd($total, $subtotal, 2);
            $stmt->execute([
                $recommendationId,
                $item['item_name'],
                $item['description'],
                $item['category'],
                $item['item_type'],
                $item['unit_label'],
                $item['quantity'],
                $item['unit_cost'],
                $item['sessions_or_days'],
                $subtotal,
                'estimated_need',
                $item['related_action'],
                'Deterministic estimate based on report volume, affected locations, duration, target audience, and recommended actions.',
                'system_estimate',
                $item['pricing_confidence'] ?? 'medium',
                'Subtotal = quantity (' . $item['quantity'] . ') x unit cost (' . $item['unit_cost'] . ') x sessions/days (' . $item['sessions_or_days'] . ').',
                1,
                'estimated',
                $index + 1,
            ]);
        }

        $allocation = $this->getGovernmentAllocation();
        $systemBudgetMax = null;
        $finalRecommended = null;
        $validationStatus = 'budget_unavailable';

        if ($allocation['exists']) {
            $campaignId = isset($campaign['id']) ? (int) $campaign['id'] : null;
            $commitments = $this->getVerifiedCommitments($campaignId);
            $available = $this->calculateAvailableBudget($allocation, $commitments);
            $systemBudgetMax = $available['safe_available'] ?? '0.00';
            if ($systemBudgetMax !== null && bccomp($systemBudgetMax, '0', 2) > 0) {
                $finalRecommended = bccomp($total, $systemBudgetMax, 2) <= 0 ? $total : $systemBudgetMax;
                $validationStatus = bccomp($total, $systemBudgetMax, 2) <= 0 ? 'estimated' : 'warning';
            } else {
                $validationStatus = 'warning';
            }
        }

        $stmt2 = $this->pdo->prepare('
            UPDATE campaign_department_ai_recommendations
            SET estimated_budget = ?, system_budget_max = ?, final_recommended_budget = ?,
                budget_validation_status = ?
            WHERE id = ?
        ');
        $stmt2->execute([$total, $systemBudgetMax, $finalRecommended, $validationStatus, $recommendationId]);

        return [
            'estimated_budget' => $total,
            'system_budget_max' => $systemBudgetMax,
            'final_recommended_budget' => $finalRecommended,
            'budget_validation_status' => $validationStatus,
            'items_generated' => count($templates),
        ];
    }

    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [$value];
        }
        return [];
    }

    private function getReservePercent(): int
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'campaign_budget_reserve_percent'");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        if ($val && is_numeric($val)) {
            return (int) $val;
        }
        $env = getenv('CAMPAIGN_BUDGET_RESERVE_PERCENT');
        if ($env && is_numeric($env)) {
            return (int) $env;
        }
        return self::RESERVE_PERCENT;
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
}
