<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\IntegrationService;
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

    /**
     * Get IntegrationService instance
     */
    private function getIntegrationService(): IntegrationService
    {
        return new IntegrationService($this->pdo);
    }

    /**
     * Log integration event (legacy endpoint)
     */
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

    /**
     * List all external systems
     */
    public function listSystems(?array $user, array $params = []): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                id,
                system_name,
                display_name,
                system_type,
                description,
                is_active,
                created_at,
                updated_at
            FROM external_systems
            ORDER BY system_name ASC
        ');
        return ['systems' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Get available systems for a module
     */
    public function getModuleSystems(?array $user, array $params = []): array
    {
        $moduleName = $params['module'] ?? $_GET['module'] ?? null;
        
        if (!$moduleName) {
            http_response_code(422);
            return ['error' => 'Module name is required'];
        }

        $service = $this->getIntegrationService();
        $systems = $service->getAvailableSystems($moduleName);

        return ['module' => $moduleName, 'systems' => $systems];
    }

    /**
     * Query external database
     */
    public function queryDatabase(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $systemName = $input['system'] ?? $params['system'] ?? null;
        $query = $input['query'] ?? null;
        $queryParams = $input['params'] ?? [];
        $moduleName = $input['module'] ?? null;

        if (!$systemName || !$query) {
            http_response_code(422);
            return ['error' => 'System name and query are required'];
        }

        // Check module access
        if ($moduleName) {
            $service = $this->getIntegrationService();
            if (!$service->moduleHasAccess($moduleName, $systemName, 'read')) {
                http_response_code(403);
                return ['error' => "Module {$moduleName} does not have access to system {$systemName}"];
            }
        }

        try {
            $service = $this->getIntegrationService();
            $results = $service->queryExternalDatabase($systemName, $query, $queryParams);
            
            return [
                'system' => $systemName,
                'query' => $query,
                'results' => $results,
                'count' => count($results)
            ];
        } catch (\RuntimeException $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Query external API
     */
    public function queryApi(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $systemName = $input['system'] ?? $params['system'] ?? null;
        $endpoint = $input['endpoint'] ?? null;
        $method = strtoupper($input['method'] ?? 'GET');
        $data = $input['data'] ?? [];
        $moduleName = $input['module'] ?? null;

        if (!$systemName || !$endpoint) {
            http_response_code(422);
            return ['error' => 'System name and endpoint are required'];
        }

        // Check module access
        if ($moduleName) {
            $service = $this->getIntegrationService();
            $accessType = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) ? 'write' : 'read';
            if (!$service->moduleHasAccess($moduleName, $systemName, $accessType)) {
                http_response_code(403);
                return ['error' => "Module {$moduleName} does not have {$accessType} access to system {$systemName}"];
            }
        }

        try {
            $service = $this->getIntegrationService();
            $results = $service->queryExternalApi($systemName, $endpoint, $method, $data, $moduleName);
            
            return [
                'system' => $systemName,
                'endpoint' => $endpoint,
                'method' => $method,
                'data' => $results
            ];
        } catch (\RuntimeException $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get cached data from external system
     */
    public function getCachedData(?array $user, array $params = []): array
    {
        $systemName = $params['system'] ?? $_GET['system'] ?? null;
        $mappingName = $params['mapping'] ?? $_GET['mapping'] ?? null;
        $externalId = $params['external_id'] ?? $_GET['external_id'] ?? null;

        if (!$systemName || !$mappingName) {
            http_response_code(422);
            return ['error' => 'System name and mapping name are required'];
        }

        try {
            $service = $this->getIntegrationService();
            $results = $service->getCachedData($systemName, $mappingName, $externalId);
            
            return [
                'system' => $systemName,
                'mapping' => $mappingName,
                'data' => $results,
                'count' => count($results)
            ];
        } catch (\RuntimeException $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sync data from external system
     */
    public function syncData(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $systemName = $input['system'] ?? $params['system'] ?? null;
        $mappingName = $input['mapping'] ?? $params['mapping'] ?? null;

        if (!$systemName || !$mappingName) {
            http_response_code(422);
            return ['error' => 'System name and mapping name are required'];
        }

        try {
            $service = $this->getIntegrationService();
            $result = $service->syncExternalData($systemName, $mappingName, $user['id'] ?? null);
            
            return [
                'system' => $systemName,
                'mapping' => $mappingName,
                'sync_result' => $result
            ];
        } catch (\RuntimeException $e) {
            http_response_code(500);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get integration query logs
     */
    public function getLogs(?array $user, array $params = []): array
    {
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $systemName = $_GET['system'] ?? null;
        $moduleName = $_GET['module'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 100), 1000);
        $offset = (int)($_GET['offset'] ?? 0);

        $where = ['1=1'];
        $params_array = [];

        if ($systemName) {
            $where[] = 'es.system_name = :system_name';
            $params_array['system_name'] = $systemName;
        }

        if ($moduleName) {
            $where[] = 'iql.module_name = :module_name';
            $params_array['module_name'] = $moduleName;
        }

        if ($status) {
            $where[] = 'iql.status = :status';
            $params_array['status'] = $status;
        }

        $stmt = $this->pdo->prepare('
            SELECT 
                iql.*,
                es.system_name,
                es.display_name
            FROM integration_query_logs iql
            INNER JOIN external_systems es ON es.id = iql.system_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY iql.created_at DESC
            LIMIT :limit OFFSET :offset
        ');

        foreach ($params_array as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}





