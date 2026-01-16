<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

class ImpactService
{
    private PDO $pdo;
    private string $reportDir;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $configuredPath = getenv('UPLOAD_PATH') ?: (__DIR__ . '/../../public/uploads');
        $base = realpath($configuredPath) ?: $configuredPath;
        $this->reportDir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'reports';
    }

    public function computeCampaignMetrics(int $campaignId): array
    {
        $this->assertCampaignExists($campaignId);

        // Reach: Count of sent notifications
        $reach = (int) $this->scalar(
            'SELECT COUNT(*) FROM `campaign_department_notification_logs` WHERE campaign_id = :cid AND status = "sent"',
            ['cid' => $campaignId]
        );
        
        // Failed notifications
        $notificationsFailed = (int) $this->scalar(
            'SELECT COUNT(*) FROM `campaign_department_notification_logs` WHERE campaign_id = :cid AND status = "failed"',
            ['cid' => $campaignId]
        );

        // Attendance: Count from events linked to this campaign
        // Note: Table uses 'id' as primary key (not attendance_id)
        $attendance = (int) $this->scalar(
            'SELECT COUNT(DISTINCT a.id) FROM `campaign_department_attendance` a 
             INNER JOIN `campaign_department_events` e ON e.id = a.event_id 
             WHERE e.linked_campaign_id = :cid',
            ['cid' => $campaignId]
        );

        // Survey responses: Count from surveys linked to campaign OR to events in this campaign
        $surveyResponses = (int) $this->scalar(
            'SELECT COUNT(DISTINCT sr.id) FROM `campaign_department_survey_responses` sr 
             INNER JOIN `campaign_department_surveys` s ON s.id = sr.survey_id 
             LEFT JOIN `campaign_department_events` e ON e.id = s.event_id
             WHERE (s.campaign_id = :cid OR e.linked_campaign_id = :cid)',
            ['cid' => $campaignId]
        );

        // Average rating: Get from aggregated results for rating-type questions
        // This uses the survey_aggregated_results table which pre-computes averages
        $avgRating = $this->scalar(
            'SELECT AVG(sar.average_rating) FROM `campaign_department_survey_aggregated_results` sar
             INNER JOIN `campaign_department_surveys` s ON s.id = sar.survey_id
             LEFT JOIN `campaign_department_events` e ON e.id = s.event_id
             WHERE (s.campaign_id = :cid OR e.linked_campaign_id = :cid) 
             AND sar.average_rating IS NOT NULL',
            ['cid' => $campaignId]
        );
        $avgRating = $avgRating !== null ? round((float)$avgRating, 2) : null;

        // Audience segments: Count of segments targeted by this campaign
        $targetedSegments = (int) $this->scalar(
            'SELECT COUNT(DISTINCT segment_id) FROM `campaign_department_campaign_audience` WHERE campaign_id = :cid',
            ['cid' => $campaignId]
        );

        // Calculate rates
        $reachBase = max($reach, 1);
        $engagementRate = ($attendance + $surveyResponses) / $reachBase;
        $responseRate = $surveyResponses / $reachBase;

        return [
            'campaign_id' => $campaignId,
            'reach' => $reach,
            'notifications_failed' => $notificationsFailed,
            'attendance_count' => $attendance,
            'survey_responses' => $surveyResponses,
            'avg_rating' => $avgRating, // Added: Average rating from surveys
            'targeted_segments' => $targetedSegments, // Added: Count of audience segments
            'engagement_rate' => round($engagementRate, 4),
            'response_rate' => round($responseRate, 4),
        ];
    }

    public function generateReport(int $campaignId): array
    {
        $metrics = $this->computeCampaignMetrics($campaignId);
        $campaign = $this->fetchCampaign($campaignId);

        if (!is_dir($this->reportDir)) {
            mkdir($this->reportDir, 0775, true);
        }

        $filename = 'report_campaign_' . $campaignId . '_' . date('Ymd_His') . '.html';
        $path = $this->reportDir . DIRECTORY_SEPARATOR . $filename;
        $relativePath = 'uploads/reports/' . $filename;

        $html = $this->renderHtmlReport($campaign, $metrics);
        if (file_put_contents($path, $html) === false) {
            throw new RuntimeException('Failed to write report file');
        }

        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_evaluation_reports` (campaign_id, file_path, snapshot_json) VALUES (:cid, :file_path, :snapshot_json)');
        $stmt->execute([
            'cid' => $campaignId,
            'file_path' => $relativePath,
            'snapshot_json' => json_encode($metrics),
        ]);

        return [
            'message' => 'Report generated',
            'file_path' => $relativePath,
            'metrics' => $metrics,
        ];
    }

    private function renderHtmlReport(array $campaign, array $metrics): string
    {
        $title = htmlspecialchars($campaign['title'] ?? 'Campaign', ENT_QUOTES, 'UTF-8');
        $generatedAt = date('c');

        $rows = '';
        foreach ($metrics as $key => $val) {
            $rows .= sprintf(
                '<tr><td style="padding:6px;border:1px solid #ccc;">%s</td><td style="padding:6px;border:1px solid #ccc;">%s</td></tr>',
                htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8')
            );
        }

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Impact Report - {$title}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { margin-bottom: 4px; }
    table { border-collapse: collapse; margin-top: 12px; }
  </style>
</head>
<body>
  <h1>Impact Report</h1>
  <p><strong>Campaign:</strong> {$title}</p>
  <p><strong>Generated:</strong> {$generatedAt}</p>
  <table>
    <tbody>
      {$rows}
    </tbody>
  </table>
</body>
</html>
HTML;
    }

    private function assertCampaignExists(int $campaignId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `campaign_department_campaigns` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $campaignId]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Campaign not found');
        }
    }

    private function fetchCampaign(int $campaignId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, status, start_date, end_date FROM `campaign_department_campaigns` WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $campaignId]);
        $campaign = $stmt->fetch();
        if (!$campaign) {
            throw new RuntimeException('Campaign not found');
        }
        return $campaign;
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}





