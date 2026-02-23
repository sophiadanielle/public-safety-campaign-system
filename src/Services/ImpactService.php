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
        
        // Auto-migration: Add linked_campaign_id column if it doesn't exist
        $this->ensureLinkedCampaignIdColumn();
        
        // Auto-migration: Create survey_aggregated_results table if it doesn't exist
        $this->ensureSurveyAggregatedResultsTable();
        
        // Auto-migration: Create evaluation_reports table if it doesn't exist
        $this->ensureEvaluationReportsTable();
    }
    
    private function ensureLinkedCampaignIdColumn(): void
    {
        try {
            $checkStmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'campaign_department_events' 
                AND COLUMN_NAME = 'linked_campaign_id'");
            $hasColumn = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
            
            if (!$hasColumn) {
                error_log('ImpactService: Auto-applying migration - adding linked_campaign_id column to events table');
                $this->pdo->exec("ALTER TABLE `campaign_department_events` 
                    ADD COLUMN `linked_campaign_id` INT UNSIGNED NULL AFTER `campaign_id`,
                    ADD KEY `idx_linked_campaign_id` (`linked_campaign_id`)");
                error_log('ImpactService: Successfully added linked_campaign_id column');
            }
        } catch (\Exception $e) {
            error_log('ImpactService: Failed to add linked_campaign_id column: ' . $e->getMessage());
        }
    }
    
    private function ensureSurveyAggregatedResultsTable(): void
    {
        try {
            $checkStmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'campaign_department_survey_aggregated_results'");
            $hasTable = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
            
            if (!$hasTable) {
                error_log('ImpactService: Auto-applying migration - creating survey_aggregated_results table');
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `campaign_department_survey_aggregated_results` (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    survey_id INT UNSIGNED NOT NULL,
                    question_id INT UNSIGNED NOT NULL,
                    average_rating DECIMAL(5,2) NULL COMMENT 'For rating questions',
                    response_distribution JSON NULL COMMENT 'Distribution of responses',
                    total_responses INT UNSIGNED NOT NULL DEFAULT 0,
                    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_aggregated_results (survey_id, question_id),
                    INDEX idx_aggregated_results_survey (survey_id),
                    INDEX idx_aggregated_results_question (question_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log('ImpactService: Successfully created survey_aggregated_results table');
            }
        } catch (\Exception $e) {
            error_log('ImpactService: Failed to create survey_aggregated_results table: ' . $e->getMessage());
        }
    }
    
    private function ensureEvaluationReportsTable(): void
    {
        try {
            $checkStmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'campaign_department_evaluation_reports'");
            $hasTable = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
            
            if (!$hasTable) {
                error_log('ImpactService: Auto-applying migration - creating evaluation_reports table');
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS `campaign_department_evaluation_reports` (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    campaign_id INT UNSIGNED NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    snapshot_json JSON NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_eval_reports_campaign (campaign_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                error_log('ImpactService: Successfully created evaluation_reports table');
            }
        } catch (\Exception $e) {
            error_log('ImpactService: Failed to create evaluation_reports table: ' . $e->getMessage());
        }
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

        // Use Asia/Manila timezone for created_at timestamp
        $manilaTimezone = new \DateTimeZone('Asia/Manila');
        $now = new \DateTime('now', $manilaTimezone);
        $createdAt = $now->format('Y-m-d H:i:s');
        
        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_evaluation_reports` (campaign_id, file_path, snapshot_json, created_at) VALUES (:cid, :file_path, :snapshot_json, :created_at)');
        $stmt->execute([
            'cid' => $campaignId,
            'file_path' => $relativePath,
            'snapshot_json' => json_encode($metrics),
            'created_at' => $createdAt,
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





