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

        $reach = (int) $this->scalar('SELECT COUNT(*) FROM notification_logs WHERE campaign_id = :cid AND status = "sent"', ['cid' => $campaignId]);
        $notificationsFailed = (int) $this->scalar('SELECT COUNT(*) FROM notification_logs WHERE campaign_id = :cid AND status = "failed"', ['cid' => $campaignId]);

        $attendance = (int) $this->scalar(
            'SELECT COUNT(*) FROM attendance a INNER JOIN events e ON e.id = a.event_id WHERE e.campaign_id = :cid',
            ['cid' => $campaignId]
        );

        $surveyResponses = (int) $this->scalar(
            'SELECT COUNT(*) FROM survey_responses sr INNER JOIN surveys s ON s.id = sr.survey_id WHERE s.campaign_id = :cid',
            ['cid' => $campaignId]
        );

        $reachBase = max($reach, 1);
        $engagementRate = ($attendance + $surveyResponses) / $reachBase;
        $responseRate = $surveyResponses / $reachBase;

        return [
            'campaign_id' => $campaignId,
            'reach' => $reach,
            'notifications_failed' => $notificationsFailed,
            'attendance_count' => $attendance,
            'survey_responses' => $surveyResponses,
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

        $stmt = $this->pdo->prepare('INSERT INTO evaluation_reports (campaign_id, file_path, snapshot_json) VALUES (:cid, :file_path, :snapshot_json)');
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
        $stmt = $this->pdo->prepare('SELECT id FROM campaigns WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $campaignId]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('Campaign not found');
        }
    }

    private function fetchCampaign(int $campaignId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, status, start_date, end_date FROM campaigns WHERE id = :id LIMIT 1');
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





