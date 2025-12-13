<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

class IntegrationController
{
    public function __construct(
        private PDO $pdo,
        private string $jwtSecret,
        private string $jwtIssuer,
        private string $jwtAudience,
        private int $jwtExpirySeconds
    ) {
    }

    public function log(?array $user, array $params = []): array
    {
        $payload = file_get_contents('php://input');
        $body = json_decode($payload, true);
        $source = $body['source'] ?? 'external';

        $stmt = $this->pdo->prepare('INSERT INTO integration_logs (source, payload, status) VALUES (:source, :payload, :status)');
        $stmt->execute([
            'source' => $source,
            'payload' => $payload ?: '{}',
            'status' => 'success',
        ]);

        return ['message' => 'Logged', 'id' => (int) $this->pdo->lastInsertId()];
    }
}





