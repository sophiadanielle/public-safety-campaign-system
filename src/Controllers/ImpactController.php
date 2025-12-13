<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ImpactService;
use PDO;
use RuntimeException;

class ImpactController
{
    private ImpactService $impactService;

    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
        $this->impactService = new ImpactService($pdo);
    }

    public function metrics(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['id'] ?? 0);
        if ($campaignId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid campaign id'];
        }

        try {
            $metrics = $this->impactService->computeCampaignMetrics($campaignId);
            return ['data' => $metrics];
        } catch (RuntimeException $e) {
            http_response_code(404);
            return ['error' => $e->getMessage()];
        }
    }

    public function generateReport(?array $user, array $params = []): array
    {
        $campaignId = (int) ($params['campaign_id'] ?? 0);
        if ($campaignId <= 0) {
            http_response_code(400);
            return ['error' => 'Invalid campaign id'];
        }

        try {
            return $this->impactService->generateReport($campaignId);
        } catch (RuntimeException $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }
}





