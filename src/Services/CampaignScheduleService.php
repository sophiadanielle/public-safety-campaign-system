<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class CampaignScheduleService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateSchedule(int $recommendationId, int $campaignId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, title, start_date, end_date, description, objectives, barangay_target_zones
            FROM campaign_department_campaigns
            WHERE id = ?
        ");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campaign) {
            return ['error' => 'Campaign not found'];
        }

        $startDate = $campaign['start_date'] ?? date('Y-m-d');
        $endDate = $campaign['end_date'] ?? date('Y-m-d', strtotime('+90 days'));

        $totalDays = max(1, (int) date_diff(
            date_create($endDate),
            date_create($startDate)
        )->format('%a'));

        $phases = $this->buildPhases($totalDays, $campaign);
        $stored = $this->storePhases($recommendationId, $phases);

        return [
            'total_phases' => count($phases),
            'total_days' => $totalDays,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'phases' => $phases,
        ];
    }

    public function generateScheduleFromRecommendation(int $recommendationId, array $recommendation, ?array $campaign = null): array
    {
        $startDate = $campaign['start_date'] ?? $recommendation['effective_start_date'] ?? date('Y-m-d', strtotime('+7 days'));
        $duration = max(14, (int) ($recommendation['recommended_duration'] ?? 30));
        $endDate = $campaign['end_date'] ?? $recommendation['effective_end_date'] ?? date('Y-m-d', strtotime($startDate . ' +' . ($duration - 1) . ' days'));

        $scheduleCampaign = [
            'title' => $campaign['title'] ?? $recommendation['campaign_title'] ?? 'AI Recommended Campaign',
            'description' => $campaign['description'] ?? $recommendation['description'] ?? $recommendation['campaign_description'] ?? '',
            'objectives' => $campaign['objectives'] ?? $recommendation['ai_reasoning'] ?? '',
            'barangay_target_zones' => $campaign['barangay_target_zones'] ?? $recommendation['affected_locations'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $totalDays = max(1, (int) date_diff(date_create($endDate), date_create($startDate))->format('%a') + 1);
        $phases = $this->buildPhases($totalDays, $scheduleCampaign);
        $stored = $this->storePhases($recommendationId, $phases);

        return [
            'total_phases' => count($phases),
            'total_days' => $totalDays,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'phases' => $phases,
            'stored' => $stored,
        ];
    }

    private function buildPhases(int $totalDays, array $campaign): array
    {
        $phases = [];
        $currentDate = date_create($campaign['start_date'] ?? date('Y-m-d'));
        $endDate = date_create($campaign['end_date'] ?? date('Y-m-d', strtotime('+90 days')));

        $numPhases = $this->determinePhaseCount($totalDays);
        $baseDaysPerPhase = (int) floor($totalDays / $numPhases);
        $extraDays = $totalDays - ($baseDaysPerPhase * $numPhases);

        $templates = $this->getPhaseTemplates($numPhases, $campaign);

        for ($i = 0; $i < $numPhases; $i++) {
            $phaseStart = clone $currentDate;
            $duration = $baseDaysPerPhase + ($i < $extraDays ? 1 : 0);
            $phaseEnd = (clone $phaseStart)->modify('+' . ($duration - 1) . ' days');

            if ($phaseEnd > $endDate) {
                $phaseEnd = clone $endDate;
                $duration = (int) date_diff($phaseStart, $phaseEnd)->format('%a') + 1;
            }

            if ($duration <= 0) $duration = 1;

            $template = $templates[$i] ?? $templates[$numPhases - 1];

            $phases[] = [
                'sprint_number' => $i + 1,
                'sprint_title' => $template['title'],
                'start_date' => $phaseStart->format('Y-m-d'),
                'end_date' => $phaseEnd->format('Y-m-d'),
                'duration_days' => $duration,
                'objectives' => $template['objectives'],
                'activities' => json_encode($template['activities']),
                'outputs' => $template['outputs'],
                'completion_criteria' => $template['criteria'],
                'phase_budget' => '0.00',
                'sort_order' => $i + 1,
            ];

            $currentDate = (clone $phaseEnd)->modify('+1 day');
        }

        return $phases;
    }

    private function determinePhaseCount(int $totalDays): int
    {
        if ($totalDays <= 14) return 3;
        if ($totalDays <= 30) return 4;
        if ($totalDays <= 60) return 5;
        if ($totalDays <= 90) return 6;
        return 7;
    }

    private function getPhaseTemplates(int $numPhases, array $campaign): array
    {
        $campaignTitle = $campaign['title'] ?? 'Campaign';

        $basePhases = [
            [
                'title' => 'Planning and Preparation',
                'objectives' => 'Mobilize resources, finalize activity plans, and coordinate with stakeholders',
                'activities' => ['Resource inventory', 'Stakeholder coordination', 'Logistics planning', 'Team briefing'],
                'outputs' => 'Mobilization plan, resource list',
                'criteria' => 'All resources confirmed, team briefed',
            ],
            [
                'title' => 'Community Engagement and Awareness',
                'objectives' => 'Raise awareness and engage target communities about campaign objectives',
                'activities' => ['Community meetings', 'Information dissemination', 'Barangay coordination', 'Public announcements'],
                'outputs' => 'Awareness materials, meeting reports',
                'criteria' => 'Target barangays notified, materials distributed',
            ],
            [
                'title' => 'Capacity Building and Training',
                'objectives' => 'Build local capacity through training and skills development',
                'activities' => ['Training sessions', 'Workshop facilitation', 'Simulation exercises'],
                'outputs' => 'Training report, attendance records',
                'criteria' => 'Training completed, participants evaluated',
            ],
            [
                'title' => 'Core Implementation',
                'objectives' => 'Execute the main campaign activities across target areas',
                'activities' => ['On-ground activities', 'Service delivery', 'Monitoring and supervision'],
                'outputs' => 'Activity reports, service delivery logs',
                'criteria' => 'All planned activities executed',
            ],
            [
                'title' => 'Monitoring and Evaluation',
                'objectives' => 'Track progress, measure outcomes, and adjust strategies',
                'activities' => ['Data collection', 'Progress assessment', 'Stakeholder feedback', 'Mid-term review'],
                'outputs' => 'Monitoring reports, feedback summary',
                'criteria' => 'Data collected, review conducted',
            ],
            [
                'title' => 'Sustainability and Phase-Out',
                'objectives' => 'Ensure continuity of campaign benefits beyond implementation',
                'activities' => ['Sustainability planning', 'Turnover to LGU', 'Community handover'],
                'outputs' => 'Sustainability plan, turnover documents',
                'criteria' => 'Handover completed, LGU assumes management',
            ],
            [
                'title' => 'Documentation and Reporting',
                'objectives' => 'Compile results, document lessons learned, and submit final reports',
                'activities' => ['Final report preparation', 'Lessons learned documentation', 'Financial reporting', 'Close-out meeting'],
                'outputs' => 'Final report, financial report',
                'criteria' => 'Reports submitted, project closed',
            ],
        ];

        return array_slice($basePhases, 0, $numPhases);
    }

    private function storePhases(int $recommendationId, array $phases): int
    {
        $count = 0;

        $this->pdo->prepare('
            DELETE FROM campaign_ai_recommendation_schedule_phases
            WHERE recommendation_id = ?
        ')->execute([$recommendationId]);

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_ai_recommendation_schedule_phases
                (recommendation_id, sprint_number, sprint_title, start_date, end_date,
                 duration_days, objectives, activities, outputs, completion_criteria, phase_budget, status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($phases as $p) {
            $stmt->execute([
                $recommendationId,
                $p['sprint_number'],
                $p['sprint_title'],
                $p['start_date'],
                $p['end_date'],
                $p['duration_days'],
                $p['objectives'],
                $p['activities'],
                $p['outputs'],
                $p['completion_criteria'],
                $p['phase_budget'],
                'recommended',
                $p['sort_order'],
            ]);
            $count++;
        }

        return $count;
    }
}
