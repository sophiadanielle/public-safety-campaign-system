<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AutoMLService;
use PDO;
use RuntimeException;

class AutoMLController
{
    private AutoMLService $service;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $this->service = new AutoMLService($pdo);
    }

    public function predict(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $campaignId = isset($input['campaign_id']) ? (int) $input['campaign_id'] : (int) ($params['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            http_response_code(422);
            return ['error' => 'campaign_id is required'];
        }

        $features = $input['features'] ?? [];
        try {
            $prediction = $this->service->predict($campaignId, $features);
            $id = $this->service->savePrediction($campaignId, $prediction);
            return [
                'prediction_id' => $id,
                'prediction' => $prediction,
            ];
        } catch (RuntimeException $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }
}





