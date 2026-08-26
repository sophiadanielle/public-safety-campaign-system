<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class CampaignPartnerMatchingService
{
    private PDO $pdo;

    private const CAMPAIGN_TYPES = [
        'crime' => ['NGO', 'Government', 'Private Sector'],
        'disaster' => ['NGO', 'Government', 'Private Sector'],
        'general' => ['NGO', 'Government', 'Private Sector'],
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getActivePartners(): array
    {
        $statusExpr = $this->hasColumn('campaign_department_partners', 'status') ? 'status' : "'active' AS status";
        $where = $this->hasColumn('campaign_department_partners', 'status')
            ? "WHERE (status IS NULL OR status != 'archived') AND NOT (name LIKE 'AI Recommended - %' AND contact_person IS NULL AND contact_email IS NULL AND contact_phone IS NULL)"
            : "WHERE NOT (name LIKE 'AI Recommended - %' AND contact_person IS NULL AND contact_email IS NULL AND contact_phone IS NULL)";
        $stmt = $this->pdo->query("
            SELECT id, name, organization_type, {$statusExpr}, contact_person, contact_email, contact_phone
            FROM campaign_department_partners
            {$where}
            ORDER BY name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function matchPartners(int $recommendationId, int $campaignId, string $campaignCategory): array
    {
        $stmt = $this->pdo->prepare("
            SELECT category, description, barangay_target_zones
            FROM campaign_department_campaigns
            WHERE id = ?
        ");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->matchForContext($campaignCategory ?: ($campaign['category'] ?? 'crime'), $campaign ?? []);
    }

    public function matchPartnersFromRecommendation(array $recommendation, ?array $campaign = null): array
    {
        $category = (string) ($recommendation['category'] ?? $campaign['category'] ?? 'general');
        return $this->matchForContext($category, [
            'trend' => $recommendation['main_trend'] ?? $recommendation['incident_category'] ?? '',
            'actions' => $recommendation['ai_recommended_actions'] ?? [],
            'target_audience' => $recommendation['ai_target_audience'] ?? '',
        ]);
    }

    private function matchForContext(string $category, array $context): array
    {
        $partners = $this->getActivePartners();

        $matchedPartners = [];
        $suggestedTypes = [];

        $categoryKey = in_array($category, ['crime', 'disaster'], true) ? $category : 'general';
        $preferredTypes = self::CAMPAIGN_TYPES[$categoryKey] ?? self::CAMPAIGN_TYPES['general'];
        $contextText = mb_strtolower(json_encode($context));

        if (preg_match('/assault|crime|violence|theft|robbery|tanod|security/', $contextText)) {
            $preferredTypes = array_values(array_unique(array_merge(['Government', 'School'], $preferredTypes)));
        }
        if (preg_match('/disaster|earthquake|flood|weather|evacuat|medical|health/', $contextText)) {
            $preferredTypes = array_values(array_unique(array_merge(['Government', 'NGO', 'School'], $preferredTypes)));
        }

        foreach ($partners as $partner) {
            $orgType = trim(mb_strtolower($partner['organization_type'] ?? ''));
            $partnerName = trim(mb_strtolower($partner['name']));

            $isMatch = false;
            $basis = '';

            foreach ($preferredTypes as $pt) {
                if (str_contains($orgType, mb_strtolower($pt))) {
                    $isMatch = true;
                    $basis = 'Organization type matches campaign needs';
                    break;
                }
            }

            if (!$isMatch) {
                $matched = false;
                foreach ($preferredTypes as $pt) {
                    if (str_contains($partnerName, mb_strtolower($pt))) {
                        $matched = true;
                        $basis = 'Partner name suggests capability in this campaign area';
                        break;
                    }
                }
                if ($matched) {
                    $isMatch = true;
                }
            }

            if (!$isMatch) {
                $matchedPartners[] = [
                    'partner_id' => (int) $partner['id'],
                    'partner_name_snapshot' => $partner['name'],
                    'organization_type_snapshot' => $partner['organization_type'],
                    'capability_match_basis' => 'General capacity — no specific match to campaign category',
                    'capability_is_inferred' => true,
                    'recommended_role' => 'Support Partner',
                    'recommendation_reason' => 'Available active partner with general capacity',
                ];
                continue;
            }

            $matchedPartners[] = [
                'partner_id' => (int) $partner['id'],
                'partner_name_snapshot' => $partner['name'],
                'organization_type_snapshot' => $partner['organization_type'],
                'capability_match_basis' => $basis,
                'capability_is_inferred' => false,
                'recommended_role' => 'Primary Partner',
                'recommendation_reason' => 'Organization type ' . $partner['organization_type'] . ' aligned with ' . $category . ' campaign needs',
            ];
        }

        $existingTypes = array_map(function ($p) {
            return mb_strtolower(trim($p['organization_type'] ?? ''));
        }, $partners);

        foreach ($preferredTypes as $pt) {
            $found = false;
            foreach ($existingTypes as $et) {
                if (str_contains($et, mb_strtolower($pt))) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $suggestedTypes[] = $pt;
            }
        }

        if (empty($suggestedTypes)) {
            $remainingPool = $this->getActivePartners();
            $allOrgTypes = array_unique(array_filter(array_map(function ($p) {
                return $p['organization_type'];
            }, $remainingPool)));
            if (count($allOrgTypes) < 2) {
                $suggestedTypes = ['NGO', 'Government', 'Private Sector'];
            }
        }

        $suggestions = [];
        foreach (array_slice($suggestedTypes, 0, 3) as $type) {
            $suggestions[] = [
                'organization_type' => $type,
                'capability_description' => $type . ' organization with relevant expertise',
                'rationale' => 'No active ' . $type . ' partner currently available for ' . $category . ' campaign needs',
                'expected_contribution' => 'Resource provision, technical expertise, and community engagement for campaign activities',
                'search_criteria' => $type,
                'acquisition_priority' => 'medium',
            ];
        }

        return [
            'matched' => $matchedPartners,
            'suggestions' => $suggestions,
        ];
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

    public function storeMatches(int $recommendationId, array $matches): int
    {
        $count = 0;

        $this->pdo->prepare('
            DELETE FROM campaign_ai_recommendation_partners
            WHERE recommendation_id = ?
        ')->execute([$recommendationId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_partners
                (recommendation_id, partner_id, partner_name_snapshot, organization_type_snapshot,
                 capability_match_basis, capability_is_inferred, recommended_role, recommendation_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($matches['matched'] as $m) {
            $stmt->execute([
                $recommendationId,
                $m['partner_id'],
                $m['partner_name_snapshot'],
                $m['organization_type_snapshot'],
                $m['capability_match_basis'],
                $m['capability_is_inferred'] ? 1 : 0,
                $m['recommended_role'],
                $m['recommendation_reason'],
            ]);
            $count++;
        }

        return $count;
    }

    public function storeSuggestions(int $recommendationId, array $suggestions): int
    {
        $count = 0;

        $this->pdo->prepare('
            DELETE FROM campaign_ai_recommendation_partner_suggestions
            WHERE recommendation_id = ?
        ')->execute([$recommendationId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_partner_suggestions
                (recommendation_id, organization_type, capability_description, rationale,
                 expected_contribution, search_criteria, acquisition_priority)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($suggestions as $s) {
            $stmt->execute([
                $recommendationId,
                $s['organization_type'],
                $s['capability_description'],
                $s['rationale'],
                $s['expected_contribution'],
                $s['search_criteria'],
                $s['acquisition_priority'],
            ]);
            $count++;
        }

        return $count;
    }
}
