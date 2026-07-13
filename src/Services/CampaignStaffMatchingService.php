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
                    'role' => (string) $entry['role'],
                    'name' => isset($entry['name']) ? (string) $entry['name'] : null,
                ];
            }
        }
        return $roles;
    }

    public function getRecommendedParticipants(int $recommendationId, int $campaignId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, c.assigned_staff
            FROM campaign_department_ai_recommendations r
            LEFT JOIN campaign_department_campaigns c ON c.id = ?
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->execute([$campaignId, $recommendationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->getRecommendedParticipantsFromRecommendation($row, $row) : [];
    }

    public function getRecommendedParticipantsFromRecommendation(array $recommendation, ?array $campaign = null): array
    {
        $requirements = $this->buildRoleRequirements($recommendation, $campaign);
        return $this->matchRoleRequirements($requirements);
    }

    private function buildRoleRequirements(array $recommendation, ?array $campaign = null): array
    {
        $actions = $this->decodeList($recommendation['ai_recommended_actions'] ?? null);
        if (empty($actions)) {
            $actions = ['Coordinate campaign implementation and community safety activities'];
        }

        $locations = $this->decodeList($recommendation['affected_locations'] ?? $campaign['barangay_target_zones'] ?? null);
        $locationCount = max(1, count($locations));
        $reportCount = max(1, (int) ($recommendation['report_count'] ?? 1));
        $duration = max(1, (int) ($recommendation['recommended_duration'] ?? 30));
        $activityCount = max(1, count($actions));
        $audienceCount = $this->countTargetAudiences($recommendation['ai_target_audience'] ?? null);
        $priority = strtolower((string) ($recommendation['priority_level'] ?? 'medium'));
        $priorityFactor = match ($priority) {
            'critical' => 1.4,
            'high' => 1.25,
            'low' => 0.85,
            default => 1.0,
        };

        $scopeFactor = max(1, (int) ceil(($locationCount + $activityCount + $audienceCount) / 3));
        $deploymentDays = max(1, min(14, (int) ceil($duration / 7)));
        $locationLabel = $locationCount === 1
            ? (string) ($locations[0] ?? 'Primary affected location')
            : 'Multiple affected locations (' . $locationCount . ')';
        $categoryText = mb_strtolower(implode(' ', array_filter([
            $recommendation['category'] ?? '',
            $recommendation['incident_category'] ?? '',
            $recommendation['main_trend'] ?? '',
            $recommendation['campaign_title'] ?? '',
            implode(' ', array_map('strval', $actions)),
        ])));

        $requirements = [
            [
                'required_role' => 'Campaign Lead / Public Safety Coordinator',
                'required_qty' => 1,
                'deployment_location' => $locationLabel,
                'assigned_activity' => $actions[0],
                'required_capability' => 'Campaign coordination, inter-office communication, and public safety implementation oversight',
                'recommendation_reason' => 'One lead is required to coordinate planning, implementation, reporting, and escalation.',
                'staffing_priority' => 'high',
                'staffing_source' => 'Existing Staff Record',
                'deployment_days' => $deploymentDays,
            ],
            [
                'required_role' => 'Barangay Tanod / Safety Officer',
                'required_qty' => max(2, min(30, (int) ceil($locationCount * 2 * $priorityFactor), (int) ceil($reportCount / 4))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => $actions[1] ?? $actions[0],
                'required_capability' => 'Crowd guidance, route safety, patrol visibility, and incident escalation support',
                'recommendation_reason' => 'Safety personnel scale with affected locations, report volume, and priority level.',
                'staffing_priority' => in_array($priority, ['critical', 'high'], true) ? 'high' : 'medium',
                'staffing_source' => 'Request additional barangay personnel',
                'deployment_days' => $deploymentDays,
            ],
            [
                'required_role' => 'Location Coordinator',
                'required_qty' => $locationCount,
                'deployment_location' => $locationLabel,
                'assigned_activity' => $actions[2] ?? $actions[0],
                'required_capability' => 'Barangay-level coordination, attendance routing, and local issue reporting',
                'recommendation_reason' => 'Each affected location needs one point person so activities can run without duplicate staff names.',
                'staffing_priority' => $locationCount > 2 ? 'high' : 'medium',
                'staffing_source' => 'Inter-department assistance',
                'deployment_days' => $deploymentDays,
            ],
            [
                'required_role' => 'Documentation Staff',
                'required_qty' => max(1, min(6, (int) ceil($activityCount / 3), (int) ceil($locationCount / 3))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => 'Document attendance, outputs, photos, and post-campaign monitoring results',
                'required_capability' => 'Documentation, reporting, attendance tracking, and evidence capture',
                'recommendation_reason' => 'Documentation staff are required for report-backed campaign monitoring and evaluation.',
                'staffing_priority' => 'medium',
                'staffing_source' => 'Existing Staff Record',
                'deployment_days' => $deploymentDays,
            ],
            [
                'required_role' => 'Logistics Personnel',
                'required_qty' => max(1, min(8, (int) ceil($locationCount / 2), (int) ceil($activityCount / 2))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => 'Prepare materials, transportation, venue setup, and field supplies',
                'required_capability' => 'Logistics, transport coordination, materials handling, and setup support',
                'recommendation_reason' => 'Logistics need increases with locations, sessions, and duration.',
                'staffing_priority' => 'medium',
                'staffing_source' => 'Temporary deployment',
                'deployment_days' => $deploymentDays,
            ],
        ];

        if (preg_match('/disaster|flood|earthquake|fire|weather|storm|evacuat|emergency|rescue/', $categoryText)) {
            $requirements[] = [
                'required_role' => 'DRRM Officer / Emergency Response Staff',
                'required_qty' => max(1, min(8, (int) ceil($locationCount * $priorityFactor))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => 'Provide emergency preparedness guidance and response coordination',
                'required_capability' => 'Emergency preparedness, evacuation guidance, first response coordination',
                'recommendation_reason' => 'Disaster-related campaigns need verified emergency response capability.',
                'staffing_priority' => 'high',
                'staffing_source' => 'Inter-department assistance',
                'deployment_days' => $deploymentDays,
            ];
        }

        if (preg_match('/health|medical|injur|first aid|disease|sanitation/', $categoryText)) {
            $requirements[] = [
                'required_role' => 'Barangay Health Worker / Medical Personnel',
                'required_qty' => max(1, min(6, (int) ceil($locationCount * 0.75 * $priorityFactor))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => 'Support health guidance, first aid readiness, and referral messaging',
                'required_capability' => 'Basic health education, first aid readiness, and referral support',
                'recommendation_reason' => 'Health or injury-related trends require medical/health support without inventing names.',
                'staffing_priority' => 'high',
                'staffing_source' => 'Partner organization support',
                'deployment_days' => $deploymentDays,
            ];
        }

        if (preg_match('/crime|assault|theft|robbery|violence|riot|stabbing|security|peace|order/', $categoryText)) {
            $requirements[] = [
                'required_role' => 'Peace and Order Officer',
                'required_qty' => max(1, min(10, (int) ceil($scopeFactor * $priorityFactor))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => 'Support public safety briefing, incident prevention, and escalation pathways',
                'required_capability' => 'Peace and order coordination, public safety messaging, incident referral',
                'recommendation_reason' => 'Crime-related campaigns need peace and order support aligned to report severity and scope.',
                'staffing_priority' => 'high',
                'staffing_source' => 'Request additional barangay personnel',
                'deployment_days' => $deploymentDays,
            ];
        }

        if (preg_match('/youth|student|school|minor|teen|sk/', $categoryText . ' ' . mb_strtolower((string) ($recommendation['ai_target_audience'] ?? '')))) {
            $requirements[] = [
                'required_role' => 'SK Chairperson / Youth Coordinator',
                'required_qty' => max(1, min(4, (int) ceil($audienceCount * 0.5))),
                'deployment_location' => $locationLabel,
                'assigned_activity' => 'Coordinate youth audience participation and peer-focused safety messaging',
                'required_capability' => 'Youth engagement and community mobilization',
                'recommendation_reason' => 'Youth-facing campaigns need age-appropriate coordination and trusted messengers.',
                'staffing_priority' => 'medium',
                'staffing_source' => 'Volunteer support',
                'deployment_days' => $deploymentDays,
            ];
        }

        if ($campaign && !empty($campaign['assigned_staff'])) {
            foreach ($this->parseRequiredRoles((string) $campaign['assigned_staff']) as $role) {
                $requirements[] = [
                    'required_role' => $role['role'],
                    'required_qty' => 1,
                    'deployment_location' => $locationLabel,
                    'assigned_activity' => $actions[0],
                    'required_capability' => 'Existing campaign assignment capability',
                    'recommendation_reason' => 'Existing campaign assignment carried into AI planning for continuity.',
                    'staffing_priority' => 'medium',
                    'staffing_source' => 'Existing Assignment Match',
                    'deployment_days' => $deploymentDays,
                    'preferred_name' => $role['name'],
                ];
            }
        }

        return $this->mergeRequirements($requirements);
    }

    private function mergeRequirements(array $requirements): array
    {
        $merged = [];
        foreach ($requirements as $requirement) {
            $key = $this->normalizeRole($requirement['required_role']);
            if (!isset($merged[$key])) {
                $merged[$key] = $requirement;
                continue;
            }
            $merged[$key]['required_qty'] = max((int) $merged[$key]['required_qty'], (int) $requirement['required_qty']);
            $merged[$key]['staffing_priority'] = $this->higherPriority($merged[$key]['staffing_priority'], $requirement['staffing_priority']);
            if (!empty($requirement['preferred_name'])) {
                $merged[$key]['preferred_name'] = $requirement['preferred_name'];
            }
        }
        return array_values($merged);
    }

    private function matchRoleRequirements(array $requirements): array
    {
        $staffPool = $this->getStaffPool();
        $participants = [];
        $usedStaffIds = [];

        foreach ($requirements as $requirement) {
            $requiredQty = max(1, (int) $requirement['required_qty']);
            $ranked = [];
            foreach ($staffPool as $staff) {
                if (in_array((int) $staff['id'], $usedStaffIds, true)) {
                    continue;
                }
                $match = $this->roleMatch($requirement, $staff);
                if ($match['score'] > 0) {
                    $ranked[] = ['staff' => $staff, 'match' => $match];
                }
            }

            usort($ranked, static fn(array $a, array $b): int => $b['match']['score'] <=> $a['match']['score']);
            $selected = array_slice($ranked, 0, $requiredQty);
            $matchedQty = count($selected);
            $missingQty = max(0, $requiredQty - $matchedQty);

            foreach ($selected as $candidate) {
                $staff = $candidate['staff'];
                $usedStaffIds[] = (int) $staff['id'];
                $participants[] = [
                    'staff_id' => (int) $staff['id'],
                    'staff_name_snapshot' => $staff['name'],
                    'staff_role_snapshot' => $staff['role'],
                    'required_role' => $requirement['required_role'],
                    'required_qty' => $requiredQty,
                    'matched_qty' => $matchedQty,
                    'missing_qty' => $missingQty,
                    'selected_qty' => 1,
                    'deployment_location' => $requirement['deployment_location'],
                    'assigned_activity' => $requirement['assigned_activity'],
                    'required_capability' => $requirement['required_capability'],
                    'match_method' => $candidate['match']['method'],
                    'recommendation_reason' => $requirement['recommendation_reason'],
                    'staffing_priority' => $requirement['staffing_priority'],
                    'staffing_source' => 'Existing Staff Record',
                    'confirmation_status' => 'pending',
                    'availability_status' => 'Not Recorded',
                    'conflict_status' => 'unknown',
                    'conflict_note' => 'Availability and conflict data are not recorded for this staff member.',
                    'deployment_days' => $requirement['deployment_days'] ?? 1,
                ];
            }

            if ($missingQty > 0) {
                $participants[] = [
                    'staff_id' => null,
                    'staff_name_snapshot' => null,
                    'staff_role_snapshot' => $requirement['required_role'],
                    'required_role' => $requirement['required_role'],
                    'required_qty' => $requiredQty,
                    'matched_qty' => $matchedQty,
                    'missing_qty' => $missingQty,
                    'selected_qty' => 0,
                    'deployment_location' => $requirement['deployment_location'],
                    'assigned_activity' => $requirement['assigned_activity'],
                    'required_capability' => $requirement['required_capability'],
                    'match_method' => 'staffing_gap',
                    'recommendation_reason' => $requirement['recommendation_reason'],
                    'staffing_priority' => $requirement['staffing_priority'],
                    'staffing_source' => $this->suggestSource($requirement),
                    'confirmation_status' => 'pending_review',
                    'availability_status' => 'Not Recorded',
                    'conflict_status' => $matchedQty > 0 ? 'possible_conflict' : 'unknown',
                    'conflict_note' => 'Required quantity exceeds matching existing staff records. Review before creating staff or staffing requests.',
                    'deployment_days' => $requirement['deployment_days'] ?? 1,
                ];
            }
        }

        return $participants;
    }

    private function roleMatch(array $requirement, array $staff): array
    {
        $required = $this->normalizeRole($requirement['required_role']);
        $staffRole = $this->normalizeRole((string) ($staff['role'] ?? ''));
        $staffName = mb_strtolower(trim((string) ($staff['name'] ?? '')));
        $preferred = isset($requirement['preferred_name']) ? mb_strtolower(trim((string) $requirement['preferred_name'])) : '';

        if ($preferred !== '' && ($staffName === $preferred || str_contains($staffName, $preferred) || str_contains($preferred, $staffName))) {
            return ['score' => 95, 'method' => 'Existing Assignment Match'];
        }

        if ($required !== '' && ($staffRole === $required || str_contains($staffRole, $required) || str_contains($required, $staffRole))) {
            return ['score' => 90, 'method' => 'Exact Role Match'];
        }

        $groups = [
            ['tanod', 'safety', 'security', 'patrol', 'peace', 'order'],
            ['drrm', 'disaster', 'emergency', 'rescue', 'response'],
            ['health', 'medical', 'nurse', 'first aid', 'bhw'],
            ['documentation', 'information', 'communication', 'records', 'monitoring'],
            ['logistics', 'transport', 'materials', 'operations'],
            ['coordinator', 'lead', 'officer', 'administrator'],
            ['sk', 'youth', 'student'],
        ];

        foreach ($groups as $group) {
            $requiredHit = false;
            $staffHit = false;
            foreach ($group as $word) {
                $requiredHit = $requiredHit || str_contains($required, $word);
                $staffHit = $staffHit || str_contains($staffRole, $word);
            }
            if ($requiredHit && $staffHit) {
                return ['score' => 70, 'method' => 'Related Role Match'];
            }
        }

        return ['score' => 0, 'method' => 'unmatched'];
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
        $preserved = $this->getConfirmedParticipantState($recommendationId);

        $this->pdo->prepare('
            DELETE FROM campaign_ai_recommendation_participants
            WHERE recommendation_id = ?
        ')->execute([$recommendationId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_participants
                (recommendation_id, staff_id, staff_name_snapshot, staff_role_snapshot,
                 required_role, required_qty, matched_qty, missing_qty, selected_qty,
                 deployment_location, assigned_activity, required_capability,
                 match_method, recommendation_reason, staffing_priority, staffing_source,
                 confirmation_status, availability_status, conflict_status, conflict_note,
                 is_confirmed, confirmed_by, confirmed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $count = 0;
        foreach ($participants as $p) {
            $staffId = $p['staff_id'] ?? null;
            $requiredRole = (string) ($p['required_role'] ?? $p['staff_role_snapshot'] ?? '');
            $preserveKey = $this->confirmedKey($staffId === null ? null : (int) $staffId, $requiredRole);
            $confirmed = $preserved[$preserveKey] ?? null;
            $isConfirmed = $confirmed ? 1 : 0;

            $stmt->execute([
                $recommendationId,
                $staffId,
                $p['staff_name_snapshot'] ?? null,
                $p['staff_role_snapshot'] ?? null,
                $requiredRole,
                max(1, (int) ($p['required_qty'] ?? 1)),
                max(0, (int) ($p['matched_qty'] ?? 0)),
                max(0, (int) ($p['missing_qty'] ?? 0)),
                max(0, (int) ($p['selected_qty'] ?? 0)),
                $p['deployment_location'] ?? null,
                $p['assigned_activity'] ?? null,
                $p['required_capability'] ?? null,
                $p['match_method'] ?? 'unmatched',
                $p['recommendation_reason'] ?? null,
                $p['staffing_priority'] ?? 'medium',
                $p['staffing_source'] ?? null,
                $isConfirmed ? 'confirmed' : ($p['confirmation_status'] ?? 'pending'),
                $p['availability_status'] ?? 'Not Recorded',
                $p['conflict_status'] ?? 'unknown',
                $p['conflict_note'] ?? null,
                $isConfirmed,
                $confirmed['confirmed_by'] ?? null,
                $confirmed['confirmed_at'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    private function getConfirmedParticipantState(int $recommendationId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT staff_id, COALESCE(required_role, staff_role_snapshot) AS required_role,
                   confirmed_by, confirmed_at
            FROM campaign_ai_recommendation_participants
            WHERE recommendation_id = ? AND is_confirmed = 1 AND staff_id IS NOT NULL
        ');
        $stmt->execute([$recommendationId]);

        $preserved = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $preserved[$this->confirmedKey((int) $row['staff_id'], (string) $row['required_role'])] = $row;
        }
        return $preserved;
    }

    private function confirmedKey(?int $staffId, string $requiredRole): string
    {
        return (string) ($staffId ?? 0) . '|' . $this->normalizeRole($requiredRole);
    }

    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn($item) => is_scalar($item) ? trim((string) $item) : (is_array($item) ? trim((string) ($item['name'] ?? $item['location'] ?? $item['title'] ?? '')) : ''),
                $value
            )));
        }
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->decodeList($decoded);
            }
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }
        return [];
    }

    private function countTargetAudiences(mixed $value): int
    {
        $items = $this->decodeList($value);
        if (!empty($items)) {
            return max(1, count($items));
        }
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return 1;
        }
        return max(1, count(array_filter(preg_split('/[,;&]+/', $text) ?: [])));
    }

    private function normalizeRole(string $role): string
    {
        $role = mb_strtolower(trim($role));
        $role = preg_replace('/[^a-z0-9\s]+/u', ' ', $role) ?? $role;
        return trim(preg_replace('/\s+/', ' ', $role) ?? $role);
    }

    private function higherPriority(string $a, string $b): string
    {
        $rank = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        return ($rank[strtolower($b)] ?? 2) > ($rank[strtolower($a)] ?? 2) ? $b : $a;
    }

    private function suggestSource(array $requirement): string
    {
        $role = $this->normalizeRole($requirement['required_role']);
        if (str_contains($role, 'health') || str_contains($role, 'medical')) {
            return 'Partner organization support';
        }
        if (str_contains($role, 'drrm') || str_contains($role, 'emergency')) {
            return 'Inter-department assistance';
        }
        if (str_contains($role, 'tanod') || str_contains($role, 'peace') || str_contains($role, 'safety')) {
            return 'Request additional barangay personnel';
        }
        if (str_contains($role, 'youth') || str_contains($role, 'sk')) {
            return 'Volunteer support';
        }
        return $requirement['staffing_source'] ?? 'Add new Staff record';
    }
}
