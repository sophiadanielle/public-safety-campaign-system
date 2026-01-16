<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

class AutoMLService
{
    private const CACHE_TTL_HOURS = 24;
    private const CACHE_TTL_SECONDS = self::CACHE_TTL_HOURS * 3600;

    private ?string $googleAutoMLEndpoint;
    private ?string $googleApiKey;
    private ?string $googleProjectId;
    private ?string $googleRegion;
    private ?string $googleServiceAccountKey;
    private ?string $openAiApiKey;
    private bool $useGoogleAutoML;
    private bool $isTrainingConfigured;
    private bool $useOpenAI;

    public function __construct(
        private PDO $pdo,
        ?string $googleAutoMLEndpoint = null,
        ?string $googleApiKey = null,
        ?string $googleProjectId = null,
        ?string $googleRegion = null,
        ?string $googleServiceAccountKey = null,
        ?string $openAiApiKey = null
    ) {
        // getenv() returns false if not found, so we need to convert to string or null
        $envEndpoint = getenv('GOOGLE_AUTOML_ENDPOINT');
        $this->googleAutoMLEndpoint = $googleAutoMLEndpoint ?? ($envEndpoint !== false ? (string)$envEndpoint : null);
        
        $envApiKey = getenv('GOOGLE_AUTOML_API_KEY');
        $this->googleApiKey = $googleApiKey ?? ($envApiKey !== false ? (string)$envApiKey : null);
        
        $envProjectId = getenv('GOOGLE_CLOUD_PROJECT_ID');
        $this->googleProjectId = $googleProjectId ?? ($envProjectId !== false ? (string)$envProjectId : null);
        
        $envRegion = getenv('GOOGLE_CLOUD_REGION');
        $this->googleRegion = $googleRegion ?? ($envRegion !== false ? (string)$envRegion : 'us-central1');
        
        $envServiceAccount = getenv('GOOGLE_SERVICE_ACCOUNT_KEY');
        $this->googleServiceAccountKey = $googleServiceAccountKey ?? ($envServiceAccount !== false ? (string)$envServiceAccount : null);
        
        // Load OpenAI API key from environment
        $envOpenAiKey = getenv('OPENAI_API_KEY');
        $this->openAiApiKey = $openAiApiKey ?? ($envOpenAiKey !== false ? (string)$envOpenAiKey : null);
        
        $this->useGoogleAutoML = !empty($this->googleAutoMLEndpoint) && !empty($this->googleApiKey);
        $this->isTrainingConfigured = !empty($this->googleProjectId) && !empty($this->googleServiceAccountKey);
        $this->useOpenAI = !empty($this->openAiApiKey);
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

        // Use OpenAI API if configured (preferred), otherwise Google AutoML, otherwise heuristic
        if ($this->useOpenAI) {
            error_log("AutoMLService: Using OpenAI API for prediction (Campaign ID: $campaignId)");
            try {
                $result = $this->predictWithOpenAI($campaignId, $campaign, $preparedFeatures);
                error_log("AutoMLService: OpenAI prediction successful - Model: " . ($result['model_source'] ?? 'unknown'));
                return $result;
            } catch (\Exception $e) {
                error_log("AutoMLService: OpenAI prediction failed: " . $e->getMessage());
                error_log("AutoMLService: Falling back to Google AutoML or heuristic");
                // Fallback to Google AutoML or heuristics
            }
        }
        
        if ($this->useGoogleAutoML) {
            error_log("AutoMLService: Using Google AutoML for prediction (Campaign ID: $campaignId)");
            error_log("AutoMLService: Endpoint: " . ($this->googleAutoMLEndpoint ?? 'NOT SET'));
            try {
                $result = $this->predictWithGoogleAutoML($preparedFeatures);
                error_log("AutoMLService: Google AutoML prediction successful - Model: " . ($result['model_source'] ?? 'unknown'));
                return $result;
            } catch (\Exception $e) {
                error_log("AutoMLService: Google AutoML prediction failed: " . $e->getMessage());
                error_log("AutoMLService: Falling back to heuristic prediction");
                // Fallback to heuristics on exception
                $fallbackResult = $this->predictWithHeuristics($campaignId, $preparedFeatures);
                $fallbackResult['fallback_reason'] = 'Google AutoML error: ' . $e->getMessage();
                return $fallbackResult;
            }
        }

        error_log("AutoMLService: Using heuristic prediction (No AI API configured) (Campaign ID: $campaignId)");
        $heuristicResult = $this->predictWithHeuristics($campaignId, $preparedFeatures);
        $heuristicResult['automl_configured'] = false;
        return $heuristicResult;
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
                'SELECT COUNT(*) FROM campaign_department_notification_logs WHERE campaign_id = :cid AND status = "sent"',
                ['cid' => $similarId]
            );
            $totalViews += $views;
            
            // Attendance from events (count from attendance table)
            $attendance = (int) $this->scalar(
                'SELECT COALESCE(COUNT(ea.id), 0) 
                 FROM campaign_department_events e 
                 LEFT JOIN campaign_department_attendance ea ON ea.event_id = e.id 
                 WHERE e.linked_campaign_id = :cid',
                ['cid' => $similarId]
            );
            $totalAttendance += $attendance;
            
            // Ratings from feedback
            $avgRating = (float) $this->scalar(
                'SELECT AVG(f.rating) FROM campaign_department_feedback f 
                 INNER JOIN campaign_department_surveys s ON s.id = f.survey_id 
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
            'SELECT COUNT(*) FROM campaign_department_notification_logs WHERE campaign_id = :cid AND status = "sent"',
            ['cid' => $campaignId]
        );
        $currentAttendance = (int) $this->scalar(
            'SELECT COALESCE(COUNT(ea.id), 0) 
             FROM campaign_department_events e 
             LEFT JOIN campaign_department_attendance ea ON ea.event_id = e.id 
             WHERE e.linked_campaign_id = :cid',
            ['cid' => $campaignId]
        );
        $currentResponses = (int) $this->scalar(
            'SELECT COUNT(*) FROM campaign_department_survey_responses sr INNER JOIN campaign_department_surveys s ON s.id = sr.survey_id WHERE s.campaign_id = :cid',
            ['cid' => $campaignId]
        );
        $currentAvgRating = (float) $this->scalar(
            'SELECT AVG(f.rating) FROM campaign_department_feedback f 
             INNER JOIN campaign_department_surveys s ON s.id = f.survey_id 
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
            SELECT id FROM campaign_department_campaigns 
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
            SELECT e.date as event_date, e.start_time as event_time, 
                   COALESCE((SELECT COUNT(*) FROM campaign_department_attendance ea WHERE ea.event_id = e.id), 0) as attendance
            FROM campaign_department_events e 
            WHERE e.linked_campaign_id = :cid AND e.date IS NOT NULL AND e.start_time IS NOT NULL
            ORDER BY e.date DESC
        ');
        $stmt->execute(['cid' => $campaignId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Call OpenAI API for prediction
     */
    private function predictWithOpenAI(int $campaignId, array $campaign, array $features): array
    {
        // Build prompt with campaign data
        $campaignTitle = $campaign['title'] ?? 'Untitled Campaign';
        $campaignCategory = $campaign['category'] ?? 'general';
        $objectives = $campaign['objectives'] ?? '';
        $geographicScope = $campaign['geographic_scope'] ?? '';
        $startDate = $campaign['start_date'] ?? null;
        $endDate = $campaign['end_date'] ?? null;
        
        // Get historical engagement data
        $historicalEngagement = $features['historical_engagement'] ?? [];
        $avgEngagement = $features['engagement_rate'] ?? 0;
        $bestDay = $features['best_day_of_week'] ?? null;
        $bestTime = $features['best_time'] ?? null;
        
        // Build comprehensive prompt
        $prompt = "You are an AI assistant helping optimize campaign deployment timing for a public safety campaign management system.\n\n";
        $prompt .= "Campaign Details:\n";
        $prompt .= "- Title: {$campaignTitle}\n";
        $prompt .= "- Category: {$campaignCategory}\n";
        if ($objectives) {
            $prompt .= "- Objectives: {$objectives}\n";
        }
        if ($geographicScope) {
            $prompt .= "- Geographic Scope: {$geographicScope}\n";
        }
        if ($startDate) {
            $prompt .= "- Planned Start Date: {$startDate}\n";
        }
        if ($endDate) {
            $prompt .= "- Planned End Date: {$endDate}\n";
        }
        
        $prompt .= "\nHistorical Performance Data:\n";
        if ($bestDay && $bestTime) {
            $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
            $prompt .= "- Best performing day: {$dayNames[$bestDay]}\n";
            $prompt .= "- Best performing time: {$bestTime}\n";
        }
        if ($avgEngagement > 0) {
            $prompt .= "- Average engagement rate: " . round($avgEngagement * 100, 1) . "%\n";
        }
        if (!empty($historicalEngagement)) {
            $views = $historicalEngagement['views'] ?? [];
            $attendance = $historicalEngagement['attendance'] ?? [];
            if (!empty($views)) {
                $prompt .= "- Historical views: " . implode(', ', array_slice($views, 0, 5)) . "\n";
            }
            if (!empty($attendance)) {
                $prompt .= "- Historical attendance: " . implode(', ', array_slice($attendance, 0, 5)) . "\n";
            }
        }
        
        $prompt .= "\nTask: Analyze this campaign and recommend the optimal deployment date and time.\n";
        $prompt .= "Consider factors like:\n";
        $prompt .= "- Campaign category and objectives\n";
        $prompt .= "- Historical performance patterns\n";
        $prompt .= "- Day of week preferences (1=Monday, 7=Sunday)\n";
        $prompt .= "- Time of day effectiveness\n";
        $prompt .= "- Geographic scope and target audience\n\n";
        $prompt .= "Respond with a JSON object in this exact format:\n";
        $prompt .= '{"recommended_day": 1-7, "recommended_time": "HH:MM", "confidence": 0.0-1.0, "reasoning": "brief explanation"}\n';
        $prompt .= "Where recommended_day is 1 (Monday) through 7 (Sunday), recommended_time is in 24-hour format (e.g., \"14:00\"), and confidence is a decimal between 0 and 1.";
        
        // Call OpenAI API
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->openAiApiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert in campaign deployment optimization. Always respond with valid JSON only, no additional text.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 300
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("OpenAI API cURL error: $error");
            throw new RuntimeException("OpenAI API connection failed: $error");
        }
        
        if ($httpCode !== 200) {
            error_log("OpenAI API error: HTTP $httpCode");
            error_log("Response: " . ($response ? substr($response, 0, 500) : 'EMPTY'));
            throw new RuntimeException("OpenAI API returned HTTP $httpCode: " . substr($response ?: 'No response', 0, 200));
        }
        
        $result = json_decode($response, true);
        
        // Parse OpenAI response
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            // Extract JSON from response (handle cases where there's extra text)
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $pred = json_decode($jsonContent, true);
            } else {
                $pred = json_decode($content, true);
            }
            
            if ($pred && isset($pred['recommended_day']) && isset($pred['recommended_time'])) {
                $recommendedDay = (int) $pred['recommended_day'];
                $recommendedTime = $pred['recommended_time'];
                $confidence = isset($pred['confidence']) ? (float) $pred['confidence'] : 0.7;
                $reasoning = $pred['reasoning'] ?? 'AI-generated recommendation based on campaign data and historical patterns';
                
                // Validate day (1-7)
                if ($recommendedDay < 1 || $recommendedDay > 7) {
                    $recommendedDay = date('N'); // Current day of week
                }
                
                // Validate time format
                if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $recommendedTime)) {
                    $recommendedTime = '14:00'; // Default to 2 PM
                }
                
                // Convert to datetime
                $today = date('N'); // Current day of week (1-7)
                $daysUntil = ($recommendedDay - $today + 7) % 7;
                if ($daysUntil === 0) {
                    $daysUntil = 7; // Next week if same day
                }
                $baseDate = date('Y-m-d', strtotime("+{$daysUntil} days"));
                $suggestedDatetime = $baseDate . ' ' . $recommendedTime . ':00';
                
                error_log("AutoMLService: Parsed OpenAI response - Day: $recommendedDay, Time: $recommendedTime, Confidence: $confidence");
                
                return [
                    'recommended_day' => $recommendedDay,
                    'recommended_time' => $recommendedTime,
                    'suggested_datetime' => $suggestedDatetime,
                    'confidence_score' => round($confidence, 3),
                    'features_used' => $features,
                    'model_source' => 'openai_gpt4',
                    'automl_configured' => true,
                    'reasoning' => $reasoning,
                ];
            }
        }
        
        // Fallback if response format is unexpected
        error_log("AutoMLService: Unexpected OpenAI response format: " . json_encode($result));
        throw new RuntimeException("Unexpected OpenAI response format");
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

        if ($error) {
            error_log("Google AutoML cURL error: $error");
            throw new RuntimeException("Google AutoML connection failed: $error");
        }
        
        if ($httpCode !== 200) {
            // Log error for debugging
            error_log("Google AutoML API error: HTTP $httpCode");
            error_log("AutoML Endpoint: " . ($this->googleAutoMLEndpoint ?? 'NOT SET'));
            error_log("Response: " . ($response ? substr($response, 0, 500) : 'EMPTY'));
            
            // Throw exception to trigger fallback
            throw new RuntimeException("Google AutoML API returned HTTP $httpCode: " . substr($response ?: 'No response', 0, 200));
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
            $today = date('N'); // Current day of week (1-7)
            $daysUntil = ($recommendedDay - $today + 7) % 7;
            if ($daysUntil === 0) {
                $daysUntil = 7; // Next week if same day
            }
            $baseDate = date('Y-m-d', strtotime("+{$daysUntil} days"));
            $suggestedDatetime = $baseDate . ' ' . $recommendedTime . ':00';

            error_log("AutoMLService: Parsed Google AutoML response - Day: $recommendedDay, Time: $recommendedTime, Confidence: $confidence");

            return [
                'recommended_day' => (int) $recommendedDay,
                'recommended_time' => $recommendedTime,
                'suggested_datetime' => $suggestedDatetime,
                'confidence_score' => round($confidence, 3),
                'features_used' => $features,
                'model_source' => 'google_automl',
                'automl_configured' => true,
            ];
        }

        // Fallback if response format is unexpected
        error_log("AutoMLService: Unexpected Google AutoML response format: " . json_encode($result));
        throw new RuntimeException("Unexpected Google AutoML response format");
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
            SELECT id, title, category, start_date, end_date, status, geographic_scope, objectives
            FROM campaign_department_campaigns 
            WHERE id = :id LIMIT 1
        ');
        $stmt->execute(['id' => $campaignId]);
        return $stmt->fetch() ?: null;
    }

    public function savePrediction(int $campaignId, array $prediction, string $modelVersion = 'mock-1'): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO campaign_department_automl_predictions (campaign_id, model_version, prediction) VALUES (:cid, :model_version, :prediction)');
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

    // ============================================
    // ENHANCED PREDICTION METHODS
    // ============================================

    /**
     * Predict conflict risk for campaigns/events
     * Returns: conflict_probability, risk_level (low/medium/high), factors
     */
    public function predictConflictRisk(string $entityType, int $entityId, array $context = []): array
    {
        $cacheKey = $this->generateCacheKey('conflict_prediction', $entityType, $entityId, $context);
        
        // Check cache
        $cached = $this->getCachedPrediction($cacheKey, 'conflict_prediction');
        if ($cached) {
            $this->logPredictionRequest('conflict_prediction', $entityType, $entityId, $context, $cached, true, $cacheKey);
            return $cached;
        }

        // Prepare features
        $features = $this->prepareConflictFeatures($entityType, $entityId, $context);
        
        // Get active model or use heuristic
        $model = $this->getActiveModel('conflict_prediction');
        $prediction = $this->predictConflictWithModel($features, $model);

        // Cache and log
        $this->cachePrediction($cacheKey, 'conflict_prediction', $entityType, $entityId, $prediction, $features, $model['id'] ?? null);
        $this->logPredictionRequest('conflict_prediction', $entityType, $entityId, $context, $prediction, false, $cacheKey, $model['id'] ?? null);

        return $prediction;
    }

    /**
     * Predict engagement likelihood
     * Returns: engagement_likelihood, expected_attendance, confidence_score
     */
    public function predictEngagement(string $entityType, int $entityId, array $context = []): array
    {
        $cacheKey = $this->generateCacheKey('engagement_prediction', $entityType, $entityId, $context);
        
        $cached = $this->getCachedPrediction($cacheKey, 'engagement_prediction');
        if ($cached) {
            $this->logPredictionRequest('engagement_prediction', $entityType, $entityId, $context, $cached, true, $cacheKey);
            return $cached;
        }

        $model = $this->getActiveModel('engagement_prediction');
        $features = $this->prepareEngagementFeatures($entityType, $entityId, $context);
        $prediction = $this->predictEngagementWithModel($features, $model);

        $this->cachePrediction($cacheKey, 'engagement_prediction', $entityType, $entityId, $prediction, $features, $model['id'] ?? null);
        $this->logPredictionRequest('engagement_prediction', $entityType, $entityId, $context, $prediction, false, $cacheKey, $model['id'] ?? null);

        return $prediction;
    }

    /**
     * Forecast campaign readiness
     * Returns: readiness_score, is_ready, missing_components
     */
    public function forecastReadiness(int $campaignId): array
    {
        $cacheKey = $this->generateCacheKey('readiness_forecast', 'campaign', $campaignId, []);
        
        $cached = $this->getCachedPrediction($cacheKey, 'readiness_forecast');
        if ($cached) {
            $this->logPredictionRequest('readiness_forecast', 'campaign', $campaignId, [], $cached, true, $cacheKey);
            return $cached;
        }

        $model = $this->getActiveModel('readiness_forecast');
        $features = $this->prepareReadinessFeatures($campaignId);
        $prediction = $this->predictReadinessWithModel($features, $model);

        $this->cachePrediction($cacheKey, 'readiness_forecast', 'campaign', $campaignId, $prediction, $features, $model['id'] ?? null);
        $this->logPredictionRequest('readiness_forecast', 'campaign', $campaignId, [], $prediction, false, $cacheKey, $model['id'] ?? null);

        return $prediction;
    }

    // ============================================
    // TRAINING METHODS
    // ============================================

    /**
     * Check if training is configured
     */
    public function isTrainingConfigured(): bool
    {
        return $this->isTrainingConfigured;
    }

    /**
     * Start training a new model
     */
    public function startTraining(
        string $modelType,
        string $modelName,
        array $trainingData,
        string $targetColumn,
        array $featureColumns,
        ?int $createdBy = null
    ): array {
        if (!$this->isTrainingConfigured) {
            throw new RuntimeException('Google Cloud AutoML training is not configured. Set GOOGLE_CLOUD_PROJECT_ID and GOOGLE_SERVICE_ACCOUNT_KEY.');
        }

        $allowedTypes = ['schedule_optimization', 'conflict_prediction', 'engagement_prediction', 'readiness_forecast'];
        if (!in_array($modelType, $allowedTypes)) {
            throw new \InvalidArgumentException("Invalid model type: $modelType");
        }

        if (count($trainingData) < 100) {
            throw new \InvalidArgumentException('Training data must contain at least 100 examples');
        }

        $version = $this->generateVersionTag($modelType);
        $modelVersionId = $this->createModelVersionRecord($modelType, $modelName, $version, $targetColumn, $featureColumns, count($trainingData), $createdBy);
        $this->logTrainingEvent($modelVersionId, 'training_started', 'Training job initiated', ['data_size' => count($trainingData)], $createdBy);

        try {
            $datasetId = $this->uploadDataset($modelType, $trainingData, $targetColumn, $featureColumns);
            $trainingJobId = $this->createTrainingJob($modelType, $modelName, $datasetId, $targetColumn, $featureColumns);
            $modelId = $this->extractModelIdFromJob($trainingJobId);

            $this->updateModelVersionRecord($modelVersionId, [
                'dataset_id' => $datasetId,
                'training_job_id' => $trainingJobId,
                'model_id' => $modelId,
                'training_status' => 'training',
                'training_started_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logTrainingEvent($modelVersionId, 'training_progress', 'Training job submitted', ['job_id' => $trainingJobId], $createdBy);
            return $this->getModelVersion($modelVersionId);
        } catch (\Exception $e) {
            $this->updateModelVersionRecord($modelVersionId, ['training_status' => 'failed']);
            $this->logTrainingEvent($modelVersionId, 'training_failed', 'Training failed: ' . $e->getMessage(), ['error' => $e->getMessage()], $createdBy);
            throw new RuntimeException('Failed to start training: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check training status
     */
    public function checkTrainingStatus(int $modelVersionId): array
    {
        $modelVersion = $this->getModelVersion($modelVersionId);
        if (!$modelVersion || empty($modelVersion['training_job_id'])) {
            throw new RuntimeException('Model version or training job not found');
        }

        try {
            $jobStatus = $this->getTrainingJobStatus($modelVersion['training_job_id']);
            $updateData = [];

            $statusMap = [
                'JOB_STATE_PENDING' => 'pending',
                'JOB_STATE_RUNNING' => 'training',
                'JOB_STATE_SUCCEEDED' => 'completed',
                'JOB_STATE_FAILED' => 'failed',
            ];
            if (isset($jobStatus['state'])) {
                $updateData['training_status'] = $statusMap[$jobStatus['state']] ?? 'training';
            }

            if (isset($jobStatus['progress_percentage'])) {
                $this->logTrainingEvent($modelVersionId, 'training_progress', "Progress: {$jobStatus['progress_percentage']}%", ['progress' => $jobStatus['progress_percentage']]);
            }

            if ($jobStatus['state'] === 'JOB_STATE_SUCCEEDED') {
                $updateData['training_completed_at'] = date('Y-m-d H:i:s');
                if (isset($jobStatus['model_evaluation'])) {
                    $updateData['evaluation_metrics'] = json_encode($jobStatus['model_evaluation']);
                }
                $this->logTrainingEvent($modelVersionId, 'training_completed', 'Training completed', []);
            } elseif ($jobStatus['state'] === 'JOB_STATE_FAILED') {
                $updateData['training_status'] = 'failed';
                $this->logTrainingEvent($modelVersionId, 'training_failed', 'Training failed', ['error' => $jobStatus['error'] ?? 'Unknown']);
            }

            if (!empty($updateData)) {
                $this->updateModelVersionRecord($modelVersionId, $updateData);
            }

            return $this->getModelVersion($modelVersionId);
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to check training status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Deploy a trained model
     */
    public function deployModel(int $modelVersionId, ?int $deployedBy = null): array
    {
        $modelVersion = $this->getModelVersion($modelVersionId);
        if (!$modelVersion || $modelVersion['training_status'] !== 'completed') {
            throw new RuntimeException('Model must be completed before deployment');
        }

        $this->deactivateOtherModels($modelVersion['model_type'], $modelVersionId);
        $this->updateModelVersionRecord($modelVersionId, [
            'is_active' => true,
            'training_status' => 'deployed',
            'deployed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logTrainingEvent($modelVersionId, 'model_deployed', 'Model deployed for production', [], $deployedBy);
        return $this->getModelVersion($modelVersionId);
    }

    /**
     * Get active model for a type
     */
    public function getActiveModel(string $modelType): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM campaign_department_ai_model_versions 
            WHERE model_type = :type AND is_active = TRUE AND training_status = "deployed"
            ORDER BY deployed_at DESC LIMIT 1
        ');
        $stmt->execute(['type' => $modelType]);
        return $stmt->fetch() ?: null;
    }

    /**
     * List model versions
     */
    public function listModelVersions(?string $modelType = null, ?string $status = null): array
    {
        $sql = 'SELECT mv.*, u.name as created_by_name FROM campaign_department_ai_model_versions mv LEFT JOIN campaign_department_users u ON u.id = mv.created_by WHERE 1=1';
        $params = [];

        if ($modelType) {
            $sql .= ' AND mv.model_type = :type';
            $params['type'] = $modelType;
        }
        if ($status) {
            $sql .= ' AND mv.training_status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY mv.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    // ============================================
    // DATA PREPARATION METHODS
    // ============================================

    /**
     * Prepare training dataset for schedule optimization
     */
    public function prepareScheduleOptimizationData(?int $limit = null): array
    {
        $sql = '
            SELECT 
                c.id, c.category, c.geographic_scope, c.status,
                DAYOFWEEK(c.start_date) as day_of_week, MONTH(c.start_date) as month,
                HOUR(c.ai_recommended_datetime) as recommended_hour,
                c.budget, c.staff_count,
                (SELECT COUNT(*) FROM campaign_department_campaign_audience ca WHERE ca.campaign_id = c.id) as audience_segment_size,
                (SELECT COUNT(*) FROM campaign_department_notification_logs nl WHERE nl.campaign_id = c.id AND nl.status = "sent") as reach,
                (SELECT COUNT(*) FROM campaign_department_attendance a INNER JOIN campaign_department_events e ON e.id = a.event_id WHERE e.linked_campaign_id = c.id) as attendance,
                (SELECT AVG(f.rating) FROM campaign_department_feedback f INNER JOIN campaign_department_surveys s ON s.id = f.survey_id WHERE s.campaign_id = c.id) as avg_rating,
                CASE WHEN EXISTS (SELECT 1 FROM campaign_department_campaigns c2 WHERE c2.id != c.id AND ABS(TIMESTAMPDIFF(HOUR, c.ai_recommended_datetime, c2.ai_recommended_datetime)) < 2) THEN 1 ELSE 0 END as has_conflict
            FROM campaign_department_campaigns c
            WHERE c.ai_recommended_datetime IS NOT NULL AND c.start_date IS NOT NULL
        ';
        if ($limit) $sql .= " LIMIT " . (int) $limit;

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $dataset = [];
        foreach ($rows as $row) {
            $dataset[] = [
                'campaign_category' => $row['category'] ?? 'general',
                'geographic_scope' => $row['geographic_scope'] ?? 'citywide',
                'day_of_week' => (int) ($row['day_of_week'] ?? 1),
                'month' => (int) ($row['month'] ?? 1),
                'budget_range' => $this->categorizeBudget($row['budget'] ?? 0),
                'staff_count' => (int) ($row['staff_count'] ?? 0),
                'audience_segment_size' => (int) ($row['audience_segment_size'] ?? 0),
                'historical_reach' => (int) ($row['reach'] ?? 0),
                'historical_attendance' => (int) ($row['attendance'] ?? 0),
                'historical_avg_rating' => (float) ($row['avg_rating'] ?? 0),
                'has_conflict' => (int) ($row['has_conflict'] ?? 0),
                'recommended_hour' => (int) ($row['recommended_hour'] ?? 9),
                'target_optimal_time_score' => $this->calculateOptimalTimeScore($row),
            ];
        }
        return $dataset;
    }

    /**
     * Prepare training dataset for conflict prediction
     */
    public function prepareConflictPredictionData(?int $limit = null): array
    {
        $sql = '
            SELECT 
                c.id, c.category, c.geographic_scope,
                DAYOFWEEK(c.start_date) as day_of_week, HOUR(c.start_date) as hour,
                c.staff_count,
                (SELECT COUNT(*) FROM campaign_department_campaigns c2 WHERE c2.id != c.id AND c2.start_date BETWEEN DATE_SUB(c.start_date, INTERVAL 1 DAY) AND DATE_ADD(c.start_date, INTERVAL 1 DAY) AND c2.geographic_scope = c.geographic_scope) as concurrent_campaigns,
                (SELECT COUNT(*) FROM campaign_department_events e WHERE e.linked_campaign_id IS NULL AND e.date = DATE(c.start_date) AND TIME(e.start_time) BETWEEN TIME(DATE_SUB(c.start_date, INTERVAL 2 HOUR)) AND TIME(DATE_ADD(c.start_date, INTERVAL 2 HOUR))) as concurrent_events,
                CASE WHEN EXISTS (SELECT 1 FROM campaign_department_campaigns c4 WHERE c4.id != c.id AND ABS(TIMESTAMPDIFF(HOUR, c.start_date, c4.start_date)) < 2 AND c4.geographic_scope = c.geographic_scope) THEN 1 ELSE 0 END as actual_conflict
            FROM campaign_department_campaigns c
            WHERE c.start_date IS NOT NULL
        ';
        if ($limit) $sql .= " LIMIT " . (int) $limit;

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $dataset = [];
        foreach ($rows as $row) {
            $dataset[] = [
                'campaign_category' => $row['category'] ?? 'general',
                'geographic_scope' => $row['geographic_scope'] ?? 'citywide',
                'day_of_week' => (int) ($row['day_of_week'] ?? 1),
                'hour' => (int) ($row['hour'] ?? 9),
                'staff_count' => (int) ($row['staff_count'] ?? 0),
                'concurrent_campaigns' => (int) ($row['concurrent_campaigns'] ?? 0),
                'concurrent_events' => (int) ($row['concurrent_events'] ?? 0),
                'target_conflict_probability' => (int) ($row['actual_conflict'] ?? 0),
            ];
        }
        return $dataset;
    }

    /**
     * Prepare training dataset for engagement prediction
     */
    public function prepareEngagementPredictionData(?int $limit = null): array
    {
        $sql = '
            SELECT 
                c.id, c.category, c.geographic_scope,
                DAYOFWEEK(c.start_date) as day_of_week, HOUR(c.start_date) as hour,
                (SELECT COUNT(*) FROM campaign_department_campaign_audience ca WHERE ca.campaign_id = c.id) as audience_size,
                (SELECT COUNT(*) FROM campaign_department_notification_logs nl WHERE nl.campaign_id = c.id AND nl.status = "sent") as notifications_sent,
                (SELECT COUNT(*) FROM campaign_department_attendance a INNER JOIN campaign_department_events e ON e.id = a.event_id WHERE e.linked_campaign_id = c.id) as actual_attendance
            FROM campaign_department_campaigns c
            WHERE c.start_date IS NOT NULL
        ';
        if ($limit) $sql .= " LIMIT " . (int) $limit;

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $dataset = [];
        foreach ($rows as $row) {
            $attendance = (int) ($row['actual_attendance'] ?? 0);
            $notifications = (int) ($row['notifications_sent'] ?? 1);
            $engagementRate = $notifications > 0 ? ($attendance / $notifications) : 0;

            $dataset[] = [
                'campaign_category' => $row['category'] ?? 'general',
                'geographic_scope' => $row['geographic_scope'] ?? 'citywide',
                'day_of_week' => (int) ($row['day_of_week'] ?? 1),
                'hour' => (int) ($row['hour'] ?? 9),
                'audience_size' => (int) ($row['audience_size'] ?? 0),
                'notifications_sent' => $notifications,
                'target_engagement_rate' => round($engagementRate, 3),
            ];
        }
        return $dataset;
    }

    /**
     * Prepare training dataset for readiness forecast
     */
    public function prepareReadinessForecastData(?int $limit = null): array
    {
        $sql = '
            SELECT 
                c.id, c.category, c.status,
                DATEDIFF(c.start_date, c.created_at) as days_until_start,
                c.staff_count,
                (SELECT COUNT(*) FROM campaign_department_campaign_audience ca WHERE ca.campaign_id = c.id) as audience_segments_assigned,
                (SELECT COUNT(*) FROM campaign_department_campaign_content_items cci WHERE cci.campaign_id = c.id) as content_items_attached,
                (SELECT COUNT(*) FROM campaign_department_events e WHERE e.linked_campaign_id = c.id) as events_linked,
                CASE WHEN c.ai_recommended_datetime IS NOT NULL THEN 1 ELSE 0 END as has_schedule,
                CASE WHEN c.status IN ("scheduled", "ongoing", "completed") THEN 1 ELSE 0 END as is_ready
            FROM campaign_department_campaigns c
            WHERE c.start_date IS NOT NULL
        ';
        if ($limit) $sql .= " LIMIT " . (int) $limit;

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $dataset = [];
        foreach ($rows as $row) {
            $dataset[] = [
                'campaign_category' => $row['category'] ?? 'general',
                'days_until_start' => (int) ($row['days_until_start'] ?? 0),
                'staff_count' => (int) ($row['staff_count'] ?? 0),
                'audience_segments_assigned' => (int) ($row['audience_segments_assigned'] ?? 0),
                'content_items_attached' => (int) ($row['content_items_attached'] ?? 0),
                'events_linked' => (int) ($row['events_linked'] ?? 0),
                'has_schedule' => (int) ($row['has_schedule'] ?? 0),
                'target_readiness_score' => (int) ($row['is_ready'] ?? 0),
            ];
        }
        return $dataset;
    }

    /**
     * Get feature columns for a model type
     */
    public function getFeatureColumns(string $modelType): array
    {
        return match($modelType) {
            'schedule_optimization' => ['campaign_category', 'geographic_scope', 'day_of_week', 'month', 'budget_range', 'staff_count', 'audience_segment_size', 'historical_reach', 'historical_attendance', 'historical_avg_rating', 'has_conflict'],
            'conflict_prediction' => ['campaign_category', 'geographic_scope', 'day_of_week', 'hour', 'staff_count', 'concurrent_campaigns', 'concurrent_events'],
            'engagement_prediction' => ['campaign_category', 'geographic_scope', 'day_of_week', 'hour', 'audience_size', 'notifications_sent'],
            'readiness_forecast' => ['campaign_category', 'days_until_start', 'staff_count', 'audience_segments_assigned', 'content_items_attached', 'events_linked', 'has_schedule'],
            default => throw new RuntimeException("Unknown model type: $modelType"),
        };
    }

    /**
     * Get target column for a model type
     */
    public function getTargetColumn(string $modelType): string
    {
        return match($modelType) {
            'schedule_optimization' => 'target_optimal_time_score',
            'conflict_prediction' => 'target_conflict_probability',
            'engagement_prediction' => 'target_engagement_rate',
            'readiness_forecast' => 'target_readiness_score',
            default => throw new RuntimeException("Unknown model type: $modelType"),
        };
    }

    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================

    private function prepareConflictFeatures(string $entityType, int $entityId, array $context): array
    {
        if ($entityType === 'campaign') {
            $stmt = $this->pdo->prepare('SELECT category, geographic_scope, start_date, staff_count FROM campaign_department_campaigns WHERE id = :id');
            $stmt->execute(['id' => $entityId]);
            $entity = $stmt->fetch();

            if (!$entity) throw new RuntimeException("Campaign not found: $entityId");

            $concurrentCampaigns = (int) $this->pdo->query("SELECT COUNT(*) FROM campaign_department_campaigns c2 WHERE c2.id != $entityId AND c2.start_date BETWEEN DATE_SUB('{$entity['start_date']}', INTERVAL 1 DAY) AND DATE_ADD('{$entity['start_date']}', INTERVAL 1 DAY)")->fetchColumn();

            return [
                'campaign_category' => $entity['category'] ?? 'general',
                'geographic_scope' => $entity['geographic_scope'] ?? 'citywide',
                'day_of_week' => (int) date('N', strtotime($entity['start_date'])),
                'hour' => (int) date('H', strtotime($entity['start_date'])),
                'staff_count' => (int) ($entity['staff_count'] ?? 0),
                'concurrent_campaigns' => $concurrentCampaigns,
            ];
        }
        throw new RuntimeException("Unsupported entity type: $entityType");
    }

    private function prepareEngagementFeatures(string $entityType, int $entityId, array $context): array
    {
        return $this->prepareConflictFeatures($entityType, $entityId, $context);
    }

    private function prepareReadinessFeatures(int $campaignId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT category, start_date, created_at, staff_count,
                   (SELECT COUNT(*) FROM campaign_department_campaign_audience WHERE campaign_id = :id) as segments,
                   (SELECT COUNT(*) FROM campaign_department_campaign_content_items WHERE campaign_id = :id) as content,
                   (SELECT COUNT(*) FROM campaign_department_events WHERE linked_campaign_id = :id) as events,
                   ai_recommended_datetime
            FROM campaign_department_campaigns WHERE id = :id
        ');
        $stmt->execute(['id' => $campaignId]);
        $campaign = $stmt->fetch();

        if (!$campaign) throw new RuntimeException("Campaign not found: $campaignId");

        return [
            'campaign_category' => $campaign['category'] ?? 'general',
            'days_until_start' => (int) (($campaign['start_date'] && $campaign['created_at']) ? ((strtotime($campaign['start_date']) - strtotime($campaign['created_at'])) / 86400) : 0),
            'staff_count' => (int) ($campaign['staff_count'] ?? 0),
            'audience_segments_assigned' => (int) ($campaign['segments'] ?? 0),
            'content_items_attached' => (int) ($campaign['content'] ?? 0),
            'events_linked' => (int) ($campaign['events'] ?? 0),
            'has_schedule' => $campaign['ai_recommended_datetime'] ? 1 : 0,
        ];
    }

    private function predictConflictWithModel(array $features, ?array $model): array
    {
        if ($model) {
            // TODO: Call actual Vertex AI model endpoint
        }

        // Heuristic conflict prediction
        $riskScore = 0.0;
        if ($features['concurrent_campaigns'] > 2) $riskScore += 0.4;
        if ($features['staff_count'] < 3 && $features['concurrent_campaigns'] > 0) $riskScore += 0.3;

        $riskLevel = $riskScore < 0.3 ? 'low' : ($riskScore < 0.6 ? 'medium' : 'high');

        return [
            'conflict_probability' => round($riskScore, 3),
            'risk_level' => $riskLevel,
            'confidence_score' => 0.7,
            'model_source' => $model ? 'google_automl' : 'heuristic',
            'factors' => ['concurrent_campaigns' => $features['concurrent_campaigns'], 'staff_availability' => $features['staff_count']],
        ];
    }

    private function predictEngagementWithModel(array $features, ?array $model): array
    {
        if ($model) {
            // TODO: Call actual Vertex AI model endpoint
        }

        $engagementScore = 0.5;
        if (in_array($features['day_of_week'], [6, 7])) $engagementScore += 0.2;

        return [
            'engagement_likelihood' => round(min(1.0, $engagementScore), 3),
            'expected_attendance' => (int) (($features['audience_size'] ?? 0) * $engagementScore),
            'confidence_score' => 0.65,
            'model_source' => $model ? 'google_automl' : 'heuristic',
        ];
    }

    private function predictReadinessWithModel(array $features, ?array $model): array
    {
        if ($model) {
            // TODO: Call actual Vertex AI model endpoint
        }

        $readinessScore = 0.0;
        if ($features['has_schedule']) $readinessScore += 0.3;
        if ($features['audience_segments_assigned'] > 0) $readinessScore += 0.2;
        if ($features['content_items_attached'] > 0) $readinessScore += 0.2;
        if ($features['events_linked'] > 0) $readinessScore += 0.15;
        if ($features['staff_count'] > 0) $readinessScore += 0.15;

        $missing = [];
        if (!$features['has_schedule']) $missing[] = 'schedule';
        if ($features['audience_segments_assigned'] === 0) $missing[] = 'audience_segments';
        if ($features['content_items_attached'] === 0) $missing[] = 'content_items';
        if ($features['staff_count'] === 0) $missing[] = 'staff_assignment';

        return [
            'readiness_score' => round($readinessScore, 3),
            'is_ready' => $readinessScore >= 0.7,
            'missing_components' => $missing,
            'confidence_score' => 0.75,
            'model_source' => $model ? 'google_automl' : 'heuristic',
        ];
    }

    // Caching methods
    private function getCachedPrediction(string $cacheKey, string $modelType): ?array
    {
        $stmt = $this->pdo->prepare('SELECT prediction_result, confidence_score FROM campaign_department_ai_prediction_cache WHERE cache_key = :key AND expires_at > NOW()');
        $stmt->execute(['key' => $cacheKey]);
        $result = $stmt->fetch();

        if ($result) {
            $prediction = json_decode($result['prediction_result'], true);
            $prediction['from_cache'] = true;
            return $prediction;
        }
        return null;
    }

    private function cachePrediction(string $cacheKey, string $modelType, string $entityType, int $entityId, array $prediction, array $features, ?int $modelVersionId): void
    {
        $featureHash = hash('sha256', json_encode($features));
        $expiresAt = date('Y-m-d H:i:s', time() + self::CACHE_TTL_SECONDS);

        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_department_ai_prediction_cache (cache_key, model_type, model_version_id, entity_type, entity_id, feature_hash, prediction_result, confidence_score, expires_at)
            VALUES (:key, :type, :model_id, :entity_type, :entity_id, :feature_hash, :result, :confidence, :expires)
            ON DUPLICATE KEY UPDATE prediction_result = VALUES(prediction_result), confidence_score = VALUES(confidence_score), expires_at = VALUES(expires_at)
        ');
        $stmt->execute([
            'key' => $cacheKey, 'type' => $modelType, 'model_id' => $modelVersionId,
            'entity_type' => $entityType, 'entity_id' => $entityId, 'feature_hash' => $featureHash,
            'result' => json_encode($prediction), 'confidence' => $prediction['confidence_score'] ?? null, 'expires' => $expiresAt,
        ]);
    }

    private function generateCacheKey(string $modelType, string $entityType, int $entityId, array $features): string
    {
        return hash('md5', json_encode([$modelType, $entityType, $entityId, $features]));
    }

    private function logPredictionRequest(string $modelType, string $entityType, int $entityId, array $requestPayload, array $prediction, bool $usedCache, string $cacheKey, ?int $modelVersionId = null, ?int $responseTime = null): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_department_ai_prediction_requests (model_type, entity_type, entity_id, model_version_id, request_payload, prediction_result, used_cache, cache_key, response_time_ms, success, requested_by)
            VALUES (:type, :entity_type, :entity_id, :model_id, :request, :result, :cache, :cache_key, :time, :success, :user)
        ');
        $stmt->execute([
            'type' => $modelType, 'entity_type' => $entityType, 'entity_id' => $entityId, 'model_id' => $modelVersionId,
            'request' => json_encode($requestPayload), 'result' => json_encode($prediction), 'cache' => $usedCache ? 1 : 0,
            'cache_key' => $cacheKey, 'time' => $responseTime, 'success' => 1, 'user' => null,
        ]);
    }

    // Training helper methods
    private function createModelVersionRecord(string $modelType, string $modelName, string $version, string $targetColumn, array $featureColumns, int $dataSize, ?int $createdBy): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO campaign_department_ai_model_versions (model_name, model_type, training_version, target_column, feature_columns, training_data_size, project_id, region, created_by, training_status, model_id)
            VALUES (:name, :type, :version, :target, :features, :size, :project, :region, :created_by, "pending", "")
        ');
        $stmt->execute([
            'name' => $modelName, 'type' => $modelType, 'version' => $version, 'target' => $targetColumn,
            'features' => json_encode($featureColumns), 'size' => $dataSize, 'project' => $this->googleProjectId,
            'region' => $this->googleRegion, 'created_by' => $createdBy,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function updateModelVersionRecord(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        if (empty($fields)) return;
        $sql = 'UPDATE campaign_department_ai_model_versions SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $this->pdo->prepare($sql)->execute($params);
    }

    private function getModelVersion(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT mv.*, u.name as created_by_name FROM campaign_department_ai_model_versions mv LEFT JOIN campaign_department_users u ON u.id = mv.created_by WHERE mv.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function logTrainingEvent(int $modelVersionId, string $actionType, string $message, array $metadata = [], ?int $createdBy = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO campaign_department_ai_training_logs (model_version_id, action_type, message, metadata, created_by) VALUES (:model_id, :action, :message, :metadata, :created_by)');
        $stmt->execute(['model_id' => $modelVersionId, 'action' => $actionType, 'message' => $message, 'metadata' => json_encode($metadata), 'created_by' => $createdBy]);
    }

    private function deactivateOtherModels(string $modelType, int $excludeId): void
    {
        $this->pdo->prepare('UPDATE campaign_department_ai_model_versions SET is_active = FALSE WHERE model_type = :type AND id != :exclude_id AND is_active = TRUE')
            ->execute(['type' => $modelType, 'exclude_id' => $excludeId]);
    }

    private function generateVersionTag(string $modelType): string
    {
        $stmt = $this->pdo->prepare('SELECT training_version FROM campaign_department_ai_model_versions WHERE model_type = :type ORDER BY created_at DESC LIMIT 1');
        $stmt->execute(['type' => $modelType]);
        $latest = $stmt->fetchColumn();

        if ($latest && preg_match('/v(\d+)\.(\d+)\.(\d+)/', $latest, $matches)) {
            return "v{$matches[1]}.{$matches[2]}." . ((int)$matches[3] + 1);
        }
        return 'v1.0.0';
    }

    private function categorizeBudget(?float $budget): string
    {
        if (!$budget || $budget <= 0) return 'none';
        if ($budget < 10000) return 'low';
        if ($budget < 50000) return 'medium';
        return 'high';
    }

    private function calculateOptimalTimeScore(array $row): float
    {
        $attendance = (int) ($row['attendance'] ?? 0);
        $rating = (float) ($row['avg_rating'] ?? 0);
        $hasConflict = (int) ($row['has_conflict'] ?? 0);
        $reach = (int) ($row['reach'] ?? 0);

        $attendanceScore = min(1.0, $attendance / 100);
        $ratingScore = $rating / 5.0;
        $conflictPenalty = $hasConflict ? -0.3 : 0;
        $reachScore = min(1.0, $reach / 1000);

        return round(($attendanceScore * 0.4 + $ratingScore * 0.3 + $reachScore * 0.2 + $conflictPenalty), 3);
    }

    // Placeholder methods for Vertex AI integration (to be implemented with actual Google Cloud SDK)
    private function uploadDataset(string $modelType, array $trainingData, string $targetColumn, array $featureColumns): string
    {
        // TODO: Implement actual Vertex AI dataset upload
        error_log("AutoMLService: Mock dataset upload for model type: $modelType");
        return 'dataset_' . uniqid();
    }

    private function createTrainingJob(string $modelType, string $modelName, string $datasetId, string $targetColumn, array $featureColumns): string
    {
        // TODO: Implement actual Vertex AI training job creation
        error_log("AutoMLService: Mock training job creation for model: $modelName");
        return 'job_' . uniqid();
    }

    private function getTrainingJobStatus(string $jobId): array
    {
        // TODO: Implement actual Vertex AI job status check
        return ['state' => 'JOB_STATE_PENDING', 'progress_percentage' => 0];
    }

    private function extractModelIdFromJob(string $jobId): string
    {
        // TODO: Extract actual model ID from completed training job
        return 'model_' . uniqid();
    }
}





