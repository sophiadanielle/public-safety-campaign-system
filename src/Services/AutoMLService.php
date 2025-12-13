<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class AutoMLService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Simulate a prediction using simple heuristics derived from historical metrics.
     * Returns suggested datetime and confidence score inside an array.
     */
    public function predict(int $campaignId, array $features = []): array
    {
        // Gather simple historical signals
        $reach = (int) $this->scalar('SELECT COUNT(*) FROM notification_logs WHERE campaign_id = :cid AND status = "sent"', ['cid' => $campaignId]);
        $attendance = (int) $this->scalar('SELECT COUNT(*) FROM attendance a INNER JOIN events e ON e.id = a.event_id WHERE e.campaign_id = :cid', ['cid' => $campaignId]);
        $responses = (int) $this->scalar('SELECT COUNT(*) FROM survey_responses sr INNER JOIN surveys s ON s.id = sr.survey_id WHERE s.campaign_id = :cid', ['cid' => $campaignId]);

        // Heuristic: if attendance/response is low compared to reach, suggest evening slot; else morning slot.
        $engagement = ($reach > 0) ? ($attendance + $responses) / $reach : 0;
        $suggestedTime = $engagement < 0.1 ? '18:00:00' : '09:00:00';

        // If features include preferred_day, use it; else pick start_date or today.
        $campaignStart = $this->scalar('SELECT start_date FROM campaigns WHERE id = :cid', ['cid' => $campaignId]);
        $baseDate = $features['preferred_date'] ?? $campaignStart ?? date('Y-m-d');
        $suggestedDatetime = $baseDate . ' ' . $suggestedTime;

        // Confidence: scale with engagement, clamp 0.3 - 0.9
        $confidence = max(0.3, min(0.9, 0.3 + $engagement));

        $prediction = [
            'suggested_datetime' => $suggestedDatetime,
            'confidence_score' => round($confidence, 3),
            'features_used' => [
                'reach' => $reach,
                'attendance' => $attendance,
                'responses' => $responses,
                'engagement' => round($engagement, 4),
            ] + $features,
        ];

        return $prediction;
    }

    public function savePrediction(int $campaignId, array $prediction, string $modelVersion = 'mock-1'): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO automl_predictions (campaign_id, model_version, prediction) VALUES (:cid, :model_version, :prediction)');
        $stmt->execute([
            'cid' => $campaignId,
            'model_version' => $modelVersion,
            'prediction' => json_encode($prediction),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}





