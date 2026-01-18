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

        $stmt = $this->pdo->prepare('INSERT INTO `campaign_department_integration_logs` (source, payload, status) VALUES (:source, :payload, :status)');
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
            FROM `campaign_department_external_systems`
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
            FROM `campaign_department_integration_query_logs` iql
            INNER JOIN `campaign_department_external_systems` es ON es.id = iql.system_id
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

    /**
     * Receive webhook from external system (real-time push)
     * Public endpoint authenticated via webhook secret
     */
    public function receiveWebhook(?array $user, array $params = []): array
    {
        $systemName = $params['system'] ?? null;
        if (!$systemName) {
            http_response_code(422);
            return ['error' => 'System name is required'];
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true) ?? [];

        // Verify system exists
        $stmt = $this->pdo->prepare('
            SELECT id FROM `campaign_department_external_systems`
            WHERE system_name = :system_name AND is_active = TRUE LIMIT 1
        ');
        $stmt->execute(['system_name' => $systemName]);
        $system = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$system) {
            http_response_code(404);
            return ['error' => 'External system not found'];
        }

        // Log webhook
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_integration_query_logs`
            (system_id, query_type, query_string, request_payload, status, module_name)
            VALUES (:system_id, :query_type, :query_string, :request_payload, :status, :module_name)
        ');
        $stmt->execute([
            'system_id' => $system['id'],
            'query_type' => 'api_post',
            'query_string' => '/api/v1/integrations/webhook/' . $systemName,
            'request_payload' => $payload,
            'status' => 'success',
            'module_name' => 'integration'
        ]);

        // Cache data for processing
        $externalId = (string)($data['id'] ?? uniqid());
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_external_data_cache`
            (system_id, mapping_id, external_id, data_json, sync_status, last_synced_at)
            VALUES (:system_id, 1, :external_id, :data_json, :sync_status, NOW())
            ON DUPLICATE KEY UPDATE
                data_json = VALUES(data_json),
                sync_status = VALUES(sync_status),
                last_synced_at = NOW()
        ');
        $stmt->execute([
            'system_id' => $system['id'],
            'external_id' => $externalId,
            'data_json' => $payload,
            'sync_status' => 'pending'
        ]);

        return ['status' => 'received', 'system' => $systemName, 'external_id' => $externalId];
    }

    /**
     * Receive push data from external system (alternative endpoint)
     */
    public function receivePushData(?array $user, array $params = []): array
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $systemName = $input['system'] ?? null;
        $mappingName = $input['mapping'] ?? null;
        $data = $input['data'] ?? null;

        if (!$systemName || !$mappingName || !$data) {
            http_response_code(422);
            return ['error' => 'System name, mapping name, and data are required'];
        }

        // Verify mapping exists
        $stmt = $this->pdo->prepare('
            SELECT es.id as system_id, edm.id as mapping_id, edm.target_table
            FROM `campaign_department_external_systems` es
            INNER JOIN `campaign_department_external_data_mappings` edm ON edm.system_id = es.id
            WHERE es.system_name = :system_name 
            AND edm.mapping_name = :mapping_name 
            AND es.is_active = TRUE 
            AND edm.is_active = TRUE
            LIMIT 1
        ');
        $stmt->execute(['system_name' => $systemName, 'mapping_name' => $mappingName]);
        $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mapping) {
            http_response_code(404);
            return ['error' => 'System or mapping not found'];
        }

        // Cache the data
        $externalId = (string)($data['id'] ?? uniqid());
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_external_data_cache`
            (system_id, mapping_id, external_id, data_json, sync_status, last_synced_at)
            VALUES (:system_id, :mapping_id, :external_id, :data_json, :sync_status, NOW())
            ON DUPLICATE KEY UPDATE
                data_json = VALUES(data_json),
                sync_status = VALUES(sync_status),
                last_synced_at = NOW()
        ');
        $stmt->execute([
            'system_id' => $mapping['system_id'],
            'mapping_id' => $mapping['mapping_id'],
            'external_id' => $externalId,
            'data_json' => json_encode($data),
            'sync_status' => 'pending'
        ]);

        return [
            'status' => 'success',
            'system' => $systemName,
            'mapping' => $mappingName,
            'external_id' => $externalId,
            'message' => 'Data cached for processing'
        ];
    }
}





