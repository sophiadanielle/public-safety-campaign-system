# External System Integration Guide

This guide explains how to integrate submodules with external subsystems' databases and APIs.

## Overview

The integration system allows each submodule (campaigns, events, segments, etc.) to:
- Query data from external subsystems' databases
- Call external subsystem APIs
- Cache and sync data from external systems
- Reflect external data in this system's submodules

## Architecture

### Components

1. **External Systems Registry** (`external_systems` table)
   - Stores configuration for each external subsystem
   - Pre-populated with: law_enforcement, traffic_transport, fire_rescue, emergency_response, community_policing, target_audience

2. **Connection Configurations** (`external_system_connections` table)
   - Database connection details (host, port, credentials)
   - API connection details (base URL, auth tokens)

3. **Data Mappings** (`external_data_mappings` table)
   - Maps external system data to this system's schema
   - Defines field transformations and sync frequency

4. **Data Cache** (`external_data_cache` table)
   - Caches synced data from external systems
   - Reduces load on external systems

5. **Query Logs** (`integration_query_logs` table)
   - Logs all queries to external systems
   - Tracks performance and errors

## Setup

### 1. Import Migration

```sql
-- Run the migration file
SOURCE migrations/026_external_system_integration.sql;
```

### 2. Configure External System Connection

#### For Database Connection:

```sql
INSERT INTO external_system_connections 
(system_id, connection_type, db_host, db_port, db_name, db_username, db_password, db_driver, is_active)
VALUES 
(
    (SELECT id FROM external_systems WHERE system_name = 'law_enforcement'),
    'database',
    '192.168.1.100',
    3306,
    'law_enforcement_db',
    'readonly_user',
    'encrypted_password',
    'mysql',
    TRUE
);
```

#### For API Connection:

```sql
INSERT INTO external_system_connections 
(system_id, connection_type, api_base_url, api_auth_type, api_auth_token, api_timeout, is_active)
VALUES 
(
    (SELECT id FROM external_systems WHERE system_name = 'traffic_transport'),
    'api',
    'https://api.traffic-system.com/v1',
    'bearer',
    'encrypted_token',
    30,
    TRUE
);
```

### 3. Create Data Mapping

```sql
INSERT INTO external_data_mappings 
(system_id, mapping_name, source_table, target_table, mapping_config, sync_frequency, is_active)
VALUES 
(
    (SELECT id FROM external_systems WHERE system_name = 'law_enforcement'),
    'incidents_to_events',
    'incidents',
    'events',
    '{
        "field_mappings": {
            "incident_id": "external_id",
            "incident_type": "hazard_focus",
            "location": "venue",
            "reported_at": "date",
            "description": "event_description"
        },
        "external_id_field": "incident_id",
        "transformations": {
            "reported_at": "date_format"
        }
    }',
    'daily',
    TRUE
);
```

### 4. Grant Module Access

```sql
INSERT INTO module_system_mappings 
(module_name, system_id, access_type, is_active)
VALUES 
(
    'events',
    (SELECT id FROM external_systems WHERE system_name = 'law_enforcement'),
    'read',
    TRUE
);
```

## Usage in Submodules

### Example 1: Query External Database from Events Module

```php
// In EventController.php
use App\Services\IntegrationService;

public function getLawEnforcementIncidents(?array $user, array $params = []): array
{
    $service = new IntegrationService($this->pdo);
    
    // Check if module has access
    if (!$service->moduleHasAccess('events', 'law_enforcement', 'read')) {
        http_response_code(403);
        return ['error' => 'Access denied'];
    }
    
    // Query external database
    $incidents = $service->queryExternalDatabase(
        'law_enforcement',
        'SELECT * FROM incidents WHERE status = :status AND date >= :date',
        ['status' => 'active', 'date' => date('Y-m-d', strtotime('-7 days'))]
    );
    
    return ['incidents' => $incidents];
}
```

### Example 2: Query External API from Campaigns Module

```php
// In CampaignController.php
use App\Services\IntegrationService;

public function getTrafficData(?array $user, array $params = []): array
{
    $service = new IntegrationService($this->pdo);
    
    // Query external API
    $trafficData = $service->queryExternalApi(
        'traffic_transport',
        'traffic-incidents',
        'GET',
        ['date' => date('Y-m-d')],
        'campaigns' // module name for logging
    );
    
    return ['traffic_data' => $trafficData];
}
```

### Example 3: Use Cached Data

```php
// Get cached data (faster, no external call)
$service = new IntegrationService($this->pdo);
$cachedData = $service->getCachedData(
    'law_enforcement',
    'incidents_to_events'
);
```

### Example 4: Sync Data

```php
// Sync data from external system
$service = new IntegrationService($this->pdo);
$result = $service->syncExternalData(
    'law_enforcement',
    'incidents_to_events',
    $user['id']
);

// Result: ['synced' => 10, 'errors' => [], 'total' => 10]
```

## API Endpoints

### List All Systems
```
GET /api/v1/integrations/systems
Authorization: Bearer <token>
```

### Get Systems for Module
```
GET /api/v1/integrations/modules/{module}/systems
Authorization: Bearer <token>
```

### Query External Database
```
POST /api/v1/integrations/query/database
Authorization: Bearer <token>
Content-Type: application/json

{
    "system": "law_enforcement",
    "query": "SELECT * FROM incidents WHERE status = :status",
    "params": {"status": "active"},
    "module": "events"
}
```

### Query External API
```
POST /api/v1/integrations/query/api
Authorization: Bearer <token>
Content-Type: application/json

{
    "system": "traffic_transport",
    "endpoint": "traffic-incidents",
    "method": "GET",
    "data": {"date": "2025-01-15"},
    "module": "campaigns"
}
```

### Get Cached Data
```
GET /api/v1/integrations/cache?system=law_enforcement&mapping=incidents_to_events
Authorization: Bearer <token>
```

### Sync Data
```
POST /api/v1/integrations/sync
Authorization: Bearer <token>
Content-Type: application/json

{
    "system": "law_enforcement",
    "mapping": "incidents_to_events"
}
```

### Get Query Logs
```
GET /api/v1/integrations/logs?system=law_enforcement&module=events&status=success
Authorization: Bearer <token>
```

## Frontend Usage (JavaScript)

### Query External System from Events Module

```javascript
async function getLawEnforcementIncidents() {
    const token = localStorage.getItem('token');
    
    const response = await fetch('/api/v1/integrations/query/database', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            system: 'law_enforcement',
            query: 'SELECT * FROM incidents WHERE date >= :date',
            params: { date: '2025-01-01' },
            module: 'events'
        })
    });
    
    const data = await response.json();
    console.log('Incidents:', data.results);
}
```

### Get Cached Data

```javascript
async function getCachedIncidents() {
    const token = localStorage.getItem('token');
    
    const response = await fetch(
        '/api/v1/integrations/cache?system=law_enforcement&mapping=incidents_to_events',
        {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        }
    );
    
    const data = await response.json();
    console.log('Cached incidents:', data.data);
}
```

## Security Considerations

1. **Encryption**: Database passwords and API tokens should be encrypted (implement proper encryption in `IntegrationService`)
2. **Access Control**: Use `module_system_mappings` to restrict which modules can access which systems
3. **Query Validation**: Validate and sanitize all queries before execution
4. **Rate Limiting**: Implement rate limiting for external API calls
5. **Error Handling**: Never expose sensitive error messages to clients

## Best Practices

1. **Use Cached Data When Possible**: Reduces load on external systems
2. **Sync Regularly**: Set appropriate `sync_frequency` for data mappings
3. **Monitor Logs**: Regularly check `integration_query_logs` for errors
4. **Test Connections**: Test external system connections before going live
5. **Handle Failures Gracefully**: Always have fallback behavior when external systems are unavailable

## Troubleshooting

### Connection Failed
- Check connection configuration in `external_system_connections`
- Verify network connectivity
- Check credentials and permissions

### Access Denied
- Verify `module_system_mappings` entry exists
- Check `is_active` flags on both system and mapping

### Data Not Syncing
- Check `sync_frequency` setting
- Verify `mapping_config` is valid JSON
- Check `integration_query_logs` for errors

## Next Steps

1. Configure connections for your external systems
2. Create data mappings for each integration
3. Grant module access as needed
4. Test queries using the API endpoints
5. Monitor logs and adjust as needed






