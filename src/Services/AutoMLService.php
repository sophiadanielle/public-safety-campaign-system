<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

class AutoMLService
{
    private ?string $googleAutoMLEndpoint;
    private ?string $googleApiKey;
    private bool $useGoogleAutoML;

    public function __construct(
        private PDO $pdo,
        ?string $googleAutoMLEndpoint = null,
        ?string $googleApiKey = null
    ) {
        $this->googleAutoMLEndpoint = $googleAutoMLEndpoint ?? getenv('GOOGLE_AUTOML_ENDPOINT');
        $this->googleApiKey = $googleApiKey ?? getenv('GOOGLE_AUTOML_API_KEY');
        $this->useGoogleAutoML = !empty($this->googleAutoMLEndpoint) && !empty($this->googleApiKey);
    }

    /**
     * Predict optimal deployment time for a campaign.
     * Uses Google AutoML if configured, otherwise falls back to heuristic-based prediction.
     * 
     * @param int $campaignId Campaign ID
     * @param array $features Additional features (campaign_category, audience_segment_id, day_of_week_range, time_window, historical_engagement)
     * @return array Prediction result with recommended_day, recommended_time, confidence_score
     * @throws RuntimeException
     */
    public function predict(int $campaignId, array $features = []): array
    {
        // Get campaign data
        $campaign = $this->getCampaignData($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campaign not found');
        }

        // Prepare features for prediction
        $preparedFeatures = $this->prepareFeatures($campaignId, $campaign, $features);

        // Use Google AutoML if configured, otherwise use heuristic
        if ($this->useGoogleAutoML) {
            return $this->predictWithGoogleAutoML($preparedFeatures);
        }

        return $this->predictWithHeuristics($campaignId, $preparedFeatures);
    }

    /**
     * Prepare features for prediction from campaign data and historical metrics
     */
    private function prepareFeatures(int $campaignId, array $campaign, array $customFeatures): array
    {
        // Gather real-time historical engagement data from similar campaigns
        $category = $campaign['category'] ?? 'general';
        
        // Get engagement metrics from similar campaigns (same category)
        $similarCampaigns = $this->getSimilarCampaigns($category, $campaignId);
        
        // Aggregate historical data from similar campaigns
        $totalViews = 0;
        $totalAttendance = 0;
        $ratings = [];
        $engagementByDayOfWeek = [];
        $engagementByTime = [];
        
        foreach ($similarCampaigns as $similarId) {
            // Views/Reach from notification logs
            $views = (int) $this->scalar(
                'SELECT COUNT(*) FROM notification_logs WHERE campaign_id = :cid AND status = "sent"',
                ['cid' => $similarId]
            );
            $totalViews += $views;
            
            // Attendance from events
            $attendance = (int) $this->scalar(
                'SELECT COALESCE(SUM(e.attendance_count), 0) FROM events e WHERE e.campaign_id = :cid',
                ['cid' => $similarId]
            );
            $totalAttendance += $attendance;
            
            // Ratings from feedback
            $avgRating = (float) $this->scalar(
                'SELECT AVG(f.rating) FROM feedback f 
                 INNER JOIN surveys s ON s.id = f.survey_id 
                 WHERE s.campaign_id = :cid',
                ['cid' => $similarId]
            );
            if ($avgRating > 0) {
                $ratings[] = $avgRating;
            }
            
            // Get engagement by day of week and time from events
            $eventData = $this->getEventEngagementData($similarId);
            foreach ($eventData as $event) {
                if (!empty($event['event_date']) && !empty($event['event_time'])) {
                    $timestamp = strtotime($event['event_date'] . ' ' . $event['event_time']);
                    if ($timestamp !== false) {
                        $dayOfWeek = (int) date('N', $timestamp);
                        $time = date('H:i', $timestamp);
                        $attendance = (int) ($event['attendance'] ?? 0);
                        $engagementByDayOfWeek[$dayOfWeek] = ($engagementByDayOfWeek[$dayOfWeek] ?? 0) + $attendance;
                        $engagementByTime[$time] = ($engagementByTime[$time] ?? 0) + $attendance;
                    }
                }
            }
        }
        
        // Current campaign metrics (if any)
        $currentReach = (int) $this->scalar(
            'SELECT COUNT(*) FROM notification_logs WHERE campaign_id = :cid AND status = "sent"',
            ['cid' => $campaignId]
        );
        $currentAttendance = (int) $this->scalar(
            'SELECT COALESCE(SUM(e.attendance_count), 0) FROM events e WHERE e.campaign_id = :cid',
            ['cid' => $campaignId]
        );
        $currentResponses = (int) $this->scalar(
            'SELECT COUNT(*) FROM survey_responses sr INNER JOIN surveys s ON s.id = sr.survey_id WHERE s.campaign_id = :cid',
            ['cid' => $campaignId]
        );
        $currentAvgRating = (float) $this->scalar(
            'SELECT AVG(f.rating) FROM feedback f 
             INNER JOIN surveys s ON s.id = f.survey_id 
             WHERE s.campaign_id = :cid',
            ['cid' => $campaignId]
        ) ?: 0;
        
        // Combine current and historical
        $totalReach = $totalViews + $currentReach;
        $totalAttendance = $totalAttendance + $currentAttendance;
        if ($currentAvgRating > 0) {
            $ratings[] = $currentAvgRating;
        }

        // Find best day and time based on historical engagement
        $bestDay = 1;
        $bestTime = '09:00';
        if (!empty($engagementByDayOfWeek)) {
            arsort($engagementByDayOfWeek);
            $bestDay = array_key_first($engagementByDayOfWeek);
        }
        if (!empty($engagementByTime)) {
            arsort($engagementByTime);
            $bestTime = array_key_first($engagementByTime);
        }
        
        $avgRating = !empty($ratings) ? array_sum($ratings) / count($ratings) : 0;
        
        // Build features array with real-time historical data
        $features = [
            'campaign_category' => $customFeatures['campaign_category'] ?? $category,
            'audience_segment_id' => $customFeatures['audience_segment_id'] ?? null,
            'day_of_week_range' => $customFeatures['day_of_week_range'] ?? [1, 7], // Monday to Sunday
            'time_window' => $customFeatures['time_window'] ?? '09:00-18:00',
            'historical_engagement' => [
                'views' => $customFeatures['historical_engagement']['views'] ?? array_slice([$totalReach, $currentReach], 0, 10),
                'attendance' => $customFeatures['historical_engagement']['attendance'] ?? array_slice([$totalAttendance, $currentAttendance], 0, 10),
                'ratings' => $customFeatures['historical_engagement']['ratings'] ?? array_slice($ratings, 0, 10),
            ],
            'reach' => $totalReach,
            'attendance' => $totalAttendance,
            'responses' => $currentResponses,
            'engagement_rate' => $totalReach > 0 ? ($totalAttendance + $currentResponses) / $totalReach : 0,
            'best_day_of_week' => $bestDay,
            'best_time' => $bestTime,
            'avg_rating' => $avgRating,
            'engagement_by_day' => $engagementByDayOfWeek,
            'engagement_by_time' => $engagementByTime,
        ];

        return array_merge($features, $customFeatures);
    }
    
    /**
     * Get similar campaigns by category for historical data
     */
    private function getSimilarCampaigns(string $category, int $excludeId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id FROM campaigns 
            WHERE category = :category AND id != :exclude_id
            ORDER BY created_at DESC 
            LIMIT 20
        ');
        $stmt->execute(['category' => $category, 'exclude_id' => $excludeId]);
        return array_column($stmt->fetchAll(), 'id');
    }
    
    /**
     * Get event engagement data for a campaign
     */
    private function getEventEngagementData(int $campaignId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT event_date, event_time, attendance_count as attendance
            FROM events 
            WHERE campaign_id = :cid AND event_date IS NOT NULL AND event_time IS NOT NULL
            ORDER BY event_date DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Call Google AutoML Prediction API
     */
    private function predictWithGoogleAutoML(array $features): array
    {
        $payload = [
            'instances' => [
                [
                    'campaign_category' => $features['campaign_category'],
                    'day_of_week' => $features['day_of_week_range'][0] ?? 1,
                    'time_window' => $features['time_window'],
                    'historical_engagement' => $features['historical_engagement'],
                ]
            ]
        ];

        $ch = curl_init($this->googleAutoMLEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->googleApiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            // Fallback to heuristics if AutoML fails
            return $this->predictWithHeuristics(0, $features);
        }

        $result = json_decode($response, true);
        
        // Parse Google AutoML response format
        // Expected format: { "predictions": [{ "recommended_day": 1, "recommended_time": "14:00", "confidence": 0.85 }] }
        if (isset($result['predictions'][0])) {
            $pred = $result['predictions'][0];
            $recommendedDay = $pred['recommended_day'] ?? date('N'); // Day of week (1-7)
            $recommendedTime = $pred['recommended_time'] ?? '14:00';
            $confidence = (float) ($pred['confidence'] ?? $pred['confidence_score'] ?? 0.7);

            // Convert to datetime
            $baseDate = date('Y-m-d', strtotime("+{$recommendedDay} days"));
            $suggestedDatetime = $baseDate . ' ' . $recommendedTime . ':00';

            return [
                'recommended_day' => (int) $recommendedDay,
                'recommended_time' => $recommendedTime,
                'suggested_datetime' => $suggestedDatetime,
                'confidence_score' => round($confidence, 3),
                'features_used' => $features,
                'model_source' => 'google_automl',
            ];
        }

        // Fallback if response format is unexpected
        return $this->predictWithHeuristics(0, $features);
    }

    /**
     * Heuristic-based prediction (fallback)
     */
    private function predictWithHeuristics(int $campaignId, array $features): array
    {
        $engagement = $features['engagement_rate'] ?? 0;
        
        // Use best day/time from historical data if available
        $bestDay = $features['best_day_of_week'] ?? null;
        $bestTime = $features['best_time'] ?? null;
        
        if ($bestDay && $bestTime) {
            // Use historical best performing day and time
            $suggestedTime = $bestTime . ':00';
            $dayOfWeek = $bestDay;
        } else {
            // Fallback heuristic: if engagement is low, suggest evening slot; else morning slot
            $suggestedTime = $engagement < 0.1 ? '18:00:00' : '09:00:00';
            $dayOfWeek = $features['day_of_week_range'][0] ?? date('N');
        }
        
        // Calculate next occurrence of the recommended day
        $today = date('N'); // Current day of week (1-7)
        $daysUntil = ($dayOfWeek - $today + 7) % 7;
        if ($daysUntil === 0) {
            $daysUntil = 7; // Next week if same day
        }
        $baseDate = date('Y-m-d', strtotime("+{$daysUntil} days"));
        $suggestedDatetime = $baseDate . ' ' . $suggestedTime;

        // Confidence: scale with engagement and historical data availability
        $hasHistoricalData = !empty($features['engagement_by_day']) || !empty($features['engagement_by_time']);
        $baseConfidence = $hasHistoricalData ? 0.6 : 0.3;
        $confidence = max(0.3, min(0.9, $baseConfidence + min($engagement, 0.3)));

        return [
            'recommended_day' => (int) $dayOfWeek,
            'recommended_time' => substr($suggestedTime, 0, 5), // HH:MM format
            'suggested_datetime' => $suggestedDatetime,
            'confidence_score' => round($confidence, 3),
            'features_used' => $features,
            'model_source' => $hasHistoricalData ? 'heuristic_with_history' : 'heuristic',
        ];
    }

    /**
     * Get campaign data from database
     */
    private function getCampaignData(int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, title, category, start_date, end_date, status, geographic_scope
            FROM campaigns 
            WHERE id = :id LIMIT 1
        ');
        $stmt->execute(['id' => $campaignId]);
        return $stmt->fetch() ?: null;
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





