<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use Throwable;

/**
 * Integration Service
 * Handles connections and queries to external subsystems
 */
class IntegrationService
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Get external system configuration
     */
    public function getSystemConfig(string $systemName): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                es.*,
                esc.connection_type,
                esc.db_host,
                esc.db_port,
                esc.db_name,
                esc.db_username,
                esc.db_password,
                esc.db_driver,
                esc.api_base_url,
                esc.api_auth_type,
                esc.api_auth_token,
                esc.api_timeout,
                esc.config_json,
                esc.connection_status
            FROM `campaign_department_external_systems` es
            LEFT JOIN `campaign_department_external_system_connections` esc ON esc.system_id = es.id AND esc.is_active = TRUE
            WHERE es.system_name = :system_name AND es.is_active = TRUE
            LIMIT 1
        ');
        $stmt->execute(['system_name' => $systemName]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config && isset($config['db_password'])) {
            // Decrypt password (implement encryption/decryption as needed)
            $config['db_password'] = $this->decrypt($config['db_password']);
        }

        if ($config && isset($config['api_auth_token'])) {
            $config['api_auth_token'] = $this->decrypt($config['api_auth_token']);
        }

        return $config ?: null;
    }

    /**
     * Query external database
     */
    public function queryExternalDatabase(string $systemName, string $query, array $params = []): array
    {
        $config = $this->getSystemConfig($systemName);
        if (!$config || $config['connection_type'] !== 'database') {
            throw new \RuntimeException("System {$systemName} is not configured as a database connection");
        }

        try {
            // Create connection to external database
            $externalPdo = $this->createExternalDatabaseConnection($config);
            
            // Execute query
            $stmt = $externalPdo->prepare($query);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log the query
            $this->logQuery($systemName, 'select', $query, $params, $results, 'success');
            
            return $results;
        } catch (PDOException $e) {
            $this->logQuery($systemName, 'select', $query, $params, null, 'error', $e->getMessage());
            throw new \RuntimeException("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Query external API
     */
    public function queryExternalApi(
        string $systemName,
        string $endpoint,
        string $method = 'GET',
        array $data = [],
        ?string $moduleName = null
    ): array {
        $config = $this->getSystemConfig($systemName);
        if (!$config || !isset($config['api_base_url'])) {
            throw new \RuntimeException("System {$systemName} is not configured with an API endpoint");
        }

        $startTime = microtime(true);
        $url = rtrim($config['api_base_url'], '/') . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $config['api_timeout'] ?? 30,
            CURLOPT_HTTPHEADER => $this->buildApiHeaders($config),
        ]);

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $executionTime = (int)((microtime(true) - $startTime) * 1000);

        if ($error) {
            $this->logQuery($systemName, 'api_get', $endpoint, $data, null, 'error', $error, $executionTime, $moduleName);
            throw new \RuntimeException("API request failed: " . $error);
        }

        $responseData = json_decode($response, true) ?? [];
        
        $status = ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'error';
        $this->logQuery($systemName, 'api_get', $endpoint, $data, $responseData, $status, null, $executionTime, $moduleName, $httpCode);

        if ($status === 'error') {
            throw new \RuntimeException("API request failed with status {$httpCode}: " . ($responseData['error'] ?? 'Unknown error'));
        }

        return $responseData;
    }

    /**
     * Get cached data from external system
     */
    public function getCachedData(string $systemName, string $mappingName, ?string $externalId = null): array
    {
        $stmt = $this->pdo->prepare('
            SELECT edc.*
            FROM `campaign_department_external_data_cache` edc
            INNER JOIN `campaign_department_external_systems` es ON es.id = edc.system_id
            INNER JOIN `campaign_department_external_data_mappings` edm ON edm.id = edc.mapping_id
            WHERE es.system_name = :system_name 
            AND edm.mapping_name = :mapping_name
            AND edc.expires_at > NOW()
            AND edc.sync_status = "synced"
            ' . ($externalId ? 'AND edc.external_id = :external_id' : '') . '
            ORDER BY edc.updated_at DESC
        ');
        
        $params = ['system_name' => $systemName, 'mapping_name' => $mappingName];
        if ($externalId) {
            $params['external_id'] = $externalId;
        }
        
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            $row['data_json'] = json_decode($row['data_json'], true) ?? [];
            return $row;
        }, $results);
    }

    /**
     * Sync data from external system using mapping
     */
    public function syncExternalData(string $systemName, string $mappingName, ?int $userId = null): array
    {
        $stmt = $this->pdo->prepare('
            SELECT edm.*, es.system_name
            FROM `campaign_department_external_data_mappings` edm
            INNER JOIN `campaign_department_external_systems` es ON es.id = edm.system_id
            WHERE es.system_name = :system_name 
            AND edm.mapping_name = :mapping_name
            AND edm.is_active = TRUE
            LIMIT 1
        ');
        $stmt->execute(['system_name' => $systemName, 'mapping_name' => $mappingName]);
        $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mapping) {
            throw new \RuntimeException("Mapping {$mappingName} not found for system {$systemName}");
        }

        $mappingConfig = json_decode($mapping['mapping_config'], true);
        $sourceTable = $mapping['source_table'];
        $systemConfig = $this->getSystemConfig($systemName);

        // Fetch data from external system
        if ($systemConfig['connection_type'] === 'database') {
            $query = "SELECT * FROM {$sourceTable}";
            $externalData = $this->queryExternalDatabase($systemName, $query);
        } else {
            $externalData = $this->queryExternalApi($systemName, $sourceTable);
            if (isset($externalData['data'])) {
                $externalData = $externalData['data'];
            }
        }

        // Transform and cache data
        $synced = 0;
        $errors = [];

        foreach ($externalData as $item) {
            try {
                $transformed = $this->transformData($item, $mappingConfig);
                $externalId = $item[$mappingConfig['external_id_field'] ?? 'id'] ?? null;

                if (!$externalId) {
                    continue;
                }

                // Store in cache
                $this->cacheExternalData(
                    (int)$mapping['system_id'],
                    (int)$mapping['id'],
                    (string)$externalId,
                    $transformed
                );

                $synced++;
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Update mapping sync time
        $updateStmt = $this->pdo->prepare('
            UPDATE `campaign_department_external_data_mappings` 
            SET last_sync_at = NOW(),
                next_sync_at = CASE 
                    WHEN sync_frequency = "hourly" THEN DATE_ADD(NOW(), INTERVAL 1 HOUR)
                    WHEN sync_frequency = "daily" THEN DATE_ADD(NOW(), INTERVAL 1 DAY)
                    WHEN sync_frequency = "weekly" THEN DATE_ADD(NOW(), INTERVAL 1 WEEK)
                    ELSE NULL
                END
            WHERE id = :id
        ');
        $updateStmt->execute(['id' => $mapping['id']]);

        return [
            'synced' => $synced,
            'errors' => $errors,
            'total' => count($externalData)
        ];
    }

    /**
     * Check if module has access to system
     */
    public function moduleHasAccess(string $moduleName, string $systemName, string $accessType = 'read'): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT msm.access_type
            FROM `campaign_department_module_system_mappings` msm
            INNER JOIN `campaign_department_external_systems` es ON es.id = msm.system_id
            WHERE msm.module_name = :module_name
            AND es.system_name = :system_name
            AND msm.is_active = TRUE
            AND es.is_active = TRUE
        ');
        $stmt->execute(['module_name' => $moduleName, 'system_name' => $systemName]);
        $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mapping) {
            return false;
        }

        if ($accessType === 'read') {
            return in_array($mapping['access_type'], ['read', 'read_write']);
        }

        if ($accessType === 'write') {
            return in_array($mapping['access_type'], ['write', 'read_write']);
        }

        return $mapping['access_type'] === $accessType;
    }

    /**
     * Get available systems for a module
     */
    public function getAvailableSystems(string $moduleName): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                es.system_name,
                es.display_name,
                es.system_type,
                msm.access_type,
                esc.connection_status
            FROM `campaign_department_module_system_mappings` msm
            INNER JOIN `campaign_department_external_systems` es ON es.id = msm.system_id
            LEFT JOIN `campaign_department_external_system_connections` esc ON esc.system_id = es.id AND esc.is_active = TRUE
            WHERE msm.module_name = :module_name
            AND msm.is_active = TRUE
            AND es.is_active = TRUE
        ');
        $stmt->execute(['module_name' => $moduleName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create external database connection
     */
    private function createExternalDatabaseConnection(array $config): PDO
    {
        $driver = $config['db_driver'] ?? 'mysql';
        $host = $config['db_host'];
        $port = $config['db_port'] ?? 3306;
        $dbName = $config['db_name'];
        $username = $config['db_username'];
        $password = $config['db_password'];

        $dsn = match($driver) {
            'mysql' => "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
            'postgresql' => "pgsql:host={$host};port={$port};dbname={$dbName}",
            'mssql' => "sqlsrv:Server={$host},{$port};Database={$dbName}",
            'oracle' => "oci:dbname={$host}:{$port}/{$dbName}",
            default => throw new \RuntimeException("Unsupported database driver: {$driver}")
        };

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Build API headers
     */
    private function buildApiHeaders(array $config): array
    {
        $headers = ['Content-Type: application/json'];

        $authType = $config['api_auth_type'] ?? 'none';
        $token = $config['api_auth_token'] ?? '';

        match($authType) {
            'bearer' => $headers[] = "Authorization: Bearer {$token}",
            'basic' => $headers[] = "Authorization: Basic " . base64_encode($token),
            'apikey' => $headers[] = "X-API-Key: {$token}",
            default => null
        };

        return $headers;
    }

    /**
     * Transform data according to mapping config
     */
    private function transformData(array $data, array $mappingConfig): array
    {
        $transformed = [];
        $fieldMappings = $mappingConfig['field_mappings'] ?? [];

        foreach ($fieldMappings as $externalField => $localField) {
            if (isset($data[$externalField])) {
                $transformed[$localField] = $data[$externalField];
            }
        }

        // Apply transformations if defined
        if (isset($mappingConfig['transformations'])) {
            foreach ($mappingConfig['transformations'] as $field => $transform) {
                if (isset($transformed[$field])) {
                    $transformed[$field] = $this->applyTransformation($transformed[$field], $transform);
                }
            }
        }

        return $transformed;
    }

    /**
     * Apply transformation to a value
     */
    private function applyTransformation($value, string $transform): mixed
    {
        return match($transform) {
            'uppercase' => strtoupper((string)$value),
            'lowercase' => strtolower((string)$value),
            'trim' => trim((string)$value),
            'date_format' => date('Y-m-d H:i:s', strtotime((string)$value)),
            default => $value
        };
    }

    /**
     * Cache external data
     */
    private function cacheExternalData(int $systemId, int $mappingId, string $externalId, array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_external_data_cache` 
            (system_id, mapping_id, external_id, data_json, sync_status, last_synced_at, expires_at)
            VALUES (:system_id, :mapping_id, :external_id, :data_json, "synced", NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))
            ON DUPLICATE KEY UPDATE
                data_json = :data_json,
                sync_status = "synced",
                last_synced_at = NOW(),
                expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->execute([
            'system_id' => $systemId,
            'mapping_id' => $mappingId,
            'external_id' => $externalId,
            'data_json' => json_encode($data)
        ]);
    }

    /**
     * Log query
     */
    private function logQuery(
        string $systemName,
        string $queryType,
        string $query,
        ?array $requestPayload,
        ?array $responsePayload,
        string $status,
        ?string $errorMessage = null,
        ?int $executionTime = null,
        ?string $moduleName = null,
        ?int $responseStatus = null
    ): void {
        // Get system ID
        $stmt = $this->pdo->prepare('SELECT id FROM `campaign_department_external_systems` WHERE system_name = :system_name LIMIT 1');
        $stmt->execute(['system_name' => $systemName]);
        $system = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$system) {
            return;
        }

        $logStmt = $this->pdo->prepare('
            INSERT INTO `campaign_department_integration_query_logs` 
            (system_id, query_type, query_string, request_payload, response_payload, 
             response_status, execution_time_ms, status, error_message, module_name)
            VALUES (:system_id, :query_type, :query_string, :request_payload, :response_payload,
                    :response_status, :execution_time_ms, :status, :error_message, :module_name)
        ');
        $logStmt->execute([
            'system_id' => $system['id'],
            'query_type' => $queryType,
            'query_string' => $query,
            'request_payload' => $requestPayload ? json_encode($requestPayload) : null,
            'response_payload' => $responsePayload ? json_encode($responsePayload) : null,
            'response_status' => $responseStatus,
            'execution_time_ms' => $executionTime,
            'status' => $status,
            'error_message' => $errorMessage,
            'module_name' => $moduleName
        ]);
    }

    /**
     * Simple encryption/decryption (implement proper encryption in production)
     */
    private function encrypt(string $value): string
    {
        // TODO: Implement proper encryption (e.g., using openssl_encrypt)
        return base64_encode($value); // Placeholder
    }

    private function decrypt(string $value): string
    {
        // TODO: Implement proper decryption
        return base64_decode($value); // Placeholder
    }
}

