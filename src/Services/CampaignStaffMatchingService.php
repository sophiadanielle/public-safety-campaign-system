<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class CampaignStaffMatchingService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getStaffPool(): array
    {
        $qtyExpr = $this->hasColumn('campaign_department_reference_staff', 'qty') ? 'qty' : '1 AS qty';
        $stmt = $this->pdo->query("
            SELECT id, name, role, {$qtyExpr}
            FROM campaign_department_reference_staff
            ORDER BY role, name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function parseRequiredRoles(string $assignedStaffJson): array
    {
        $decoded = json_decode($assignedStaffJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $roles = [];
        foreach ($decoded as $entry) {
            if (is_string($entry)) {
                $roles[] = ['role' => $entry, 'name' => null];
            } elseif (is_array($entry) && isset($entry['role'])) {
                $roles[] = [
                    'role' => $entry['role'],
                    'name' => $entry['name'] ?? null,
                ];
            }
        }
        return $roles;
    }

    public function getRecommendedParticipants(int $recommendationId, int $campaignId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT assigned_staff
            FROM campaign_department_campaigns
            WHERE id = ?
        ");
        $stmt->execute([$campaignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['assigned_staff'])) {
            return [];
        }

        $requiredRoles = $this->parseRequiredRoles($row['assigned_staff']);
        if (empty($requiredRoles)) {
            return [];
        }

        return $this->matchRequiredRoles($requiredRoles);
    }

    public function getRecommendedParticipantsFromRecommendation(array $recommendation, ?array $campaign = null): array
    {
        $requiredRoles = [];
        if ($campaign && !empty($campaign['assigned_staff'])) {
            $requiredRoles = $this->parseRequiredRoles((string) $campaign['assigned_staff']);
        }

        if (empty($requiredRoles)) {
            $actions = $recommendation['ai_recommended_actions'] ?? [];
            if (is_string($actions)) {
                $decoded = json_decode($actions, true);
                $actions = is_array($decoded) ? $decoded : [$actions];
            }

            $text = mb_strtolower(implode(' ', array_filter([
                $recommendation['category'] ?? '',
                $recommendation['main_trend'] ?? '',
                $recommendation['incident_category'] ?? '',
                $recommendation['campaign_title'] ?? '',
                implode(' ', array_map('strval', (array) $actions)),
            ])));

            $roles = ['Information Officer', 'Barangay Tanod'];
            if (preg_match('/disaster|flood|earthquake|fire|weather|storm|evacuat|emergency/', $text)) {
                $roles[] = 'DRRM Officer';
                $roles[] = 'Barangay Health Worker';
            }
            if (preg_match('/crime|assault|theft|robbery|violence|riot|stabbing|security/', $text)) {
                $roles[] = 'Peace and Order Officer';
                $roles[] = 'SK Chairperson';
            }
            if (preg_match('/health|medical|injur|first aid/', $text)) {
                $roles[] = 'Barangay Health Worker';
            }

            foreach (array_values(array_unique($roles)) as $role) {
                $requiredRoles[] = ['role' => $role, 'name' => null];
            }
        }

        return $this->matchRequiredRoles($requiredRoles);
    }

    private function matchRequiredRoles(array $requiredRoles): array
    {
        if (empty($requiredRoles)) {
            return [];
        }

        $staffPool = $this->getStaffPool();
        $participants = [];
        $usedStaffIds = [];
        $usedNames = [];

        foreach ($requiredRoles as $req) {
            $roleKey = mb_strtolower(trim($req['role']));
            $matched = false;

            $nameMatchTarget = $req['name'] !== null ? mb_strtolower(trim($req['name'])) : null;

            foreach ($staffPool as $staff) {
                if (in_array($staff['id'], $usedStaffIds, true)) continue;

                $staffRole = mb_strtolower(trim($staff['role']));
                $staffName = mb_strtolower(trim($staff['name']));

                if (str_contains($staffRole, $roleKey) || str_contains($roleKey, $staffRole)) {
                    $participants[] = [
                        'staff_id' => (int) $staff['id'],
                        'staff_name_snapshot' => $staff['name'],
                        'staff_role_snapshot' => $staff['role'],
                        'match_method' => 'role_match',
                        'matched_role' => $req['role'],
                        'availability_status' => 'Not Recorded',
                        'conflict_status' => 'unknown',
                        'conflict_note' => null,
                    ];
                    $usedStaffIds[] = $staff['id'];
                    $usedNames[] = $staff['name'];
                    $matched = true;
                    break;
                }

                if ($nameMatchTarget !== null && !$matched) {
                    if (
                        str_contains($staffName, $nameMatchTarget) ||
                        str_contains($nameMatchTarget, $staffName) ||
                        levenshtein($staffName, $nameMatchTarget) <= 3
                    ) {
                        $participants[] = [
                            'staff_id' => (int) $staff['id'],
                            'staff_name_snapshot' => $staff['name'],
                            'staff_role_snapshot' => $staff['role'],
                            'match_method' => 'name_match',
                            'matched_role' => $req['role'],
                            'availability_status' => 'Not Recorded',
                            'conflict_status' => 'unknown',
                            'conflict_note' => null,
                        ];
                        $usedStaffIds[] = $staff['id'];
                        $usedNames[] = $staff['name'];
                        $matched = true;
                    }
                }
            }

            if (!$matched) {
                $participants[] = [
                    'staff_id' => null,
                    'staff_name_snapshot' => $req['name'] ?? $req['role'],
                    'staff_role_snapshot' => $req['role'],
                    'match_method' => 'unmatched',
                    'matched_role' => $req['role'],
                    'availability_status' => 'Not Recorded',
                    'conflict_status' => 'possible_conflict',
                    'conflict_note' => 'Possible Conflict: name-string match only — no staff member matched role "' . $req['role'] . '"',
                ];
            }
        }

        $nameCounts = array_count_values($usedNames);
        foreach ($participants as &$p) {
            if (
                $p['staff_name_snapshot'] !== null &&
                isset($nameCounts[$p['staff_name_snapshot']]) &&
                $nameCounts[$p['staff_name_snapshot']] > 1
            ) {
                $p['conflict_status'] = 'possible_conflict';
                $p['conflict_note'] = 'Possible Conflict: name-string match only — "' . $p['staff_name_snapshot'] . '" is assigned to multiple roles in this campaign';
            }
        }
        unset($p);

        return $participants;
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

    public function storeParticipants(int $recommendationId, array $participants): int
    {
        $count = 0;

        $this->pdo->prepare('
            DELETE FROM campaign_ai_recommendation_participants
            WHERE recommendation_id = ?
        ')->execute([$recommendationId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_participants
                (recommendation_id, staff_id, staff_name_snapshot, staff_role_snapshot,
                 match_method, recommendation_reason, availability_status, conflict_status, conflict_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($participants as $p) {
            $reason = 'Role match for "' . $p['staff_role_snapshot'] . '" required by campaign';
            $stmt->execute([
                $recommendationId,
                $p['staff_id'],
                $p['staff_name_snapshot'],
                $p['staff_role_snapshot'],
                $p['match_method'],
                $reason,
                $p['availability_status'],
                $p['conflict_status'],
                $p['conflict_note'],
            ]);
            $count++;
        }

        return $count;
    }
}
