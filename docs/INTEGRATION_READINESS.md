# Integration System Readiness for Real-Time Data

## ✅ **READY FOR REAL-TIME DATA INPUT**

All SQL migrations are **ready** to accept real-time data from:
- Internal modules (campaigns, events, surveys, etc.)
- External submodules/systems

## Database Schema Status

### ✅ Core Tables (Ready)
- All tables use `campaign_department_` prefix
- Proper indexes for performance
- Foreign keys for data integrity
- Timestamps for tracking (`created_at`, `updated_at`, `submission_timestamp`)

### ✅ Integration Infrastructure (Ready)
**Migration 026** provides:
- `campaign_department_external_systems` - Registry of external systems
- `campaign_department_external_system_connections` - Connection configs (DB/API)
- `campaign_department_external_data_mappings` - Data transformation rules
- `campaign_department_external_data_cache` - Cached synced data
- `campaign_department_integration_query_logs` - Audit trail
- `campaign_department_module_system_mappings` - Module access control

### ✅ Integration Checkpoints (Ready)
- `campaign_department_event_integration_checkpoints` - Event module
- `campaign_department_survey_integration_checkpoints` - Survey module

## Real-Time Data Flow

### 1. **PULL-Based (Query External Systems)**
✅ **READY** - System can query external systems:
- Database queries via `IntegrationService::queryExternalDatabase()`
- API calls via `IntegrationService::queryExternalApi()`
- Endpoints: `POST /api/v1/integrations/query/database` and `/query/api`

### 2. **PUSH-Based (Receive from External Systems)**
✅ **NOW READY** - External systems can push data:
- **Webhook Endpoint**: `POST /api/v1/integrations/webhook/{system}`
  - Public endpoint (authenticated via webhook secret)
  - Accepts JSON payloads
  - Automatically caches data
  
- **Push Data Endpoint**: `POST /api/v1/integrations/push`
  - Requires system name, mapping name, and data
  - Validates mapping configuration
  - Stores in cache for processing

## Sync Frequencies Supported

The `external_data_mappings` table supports:
- `'realtime'` - Immediate processing
- `'hourly'` - Hourly sync
- `'daily'` - Daily sync
- `'weekly'` - Weekly sync
- `'manual'` - On-demand sync

## How External Systems Send Data

### Option 1: Webhook (Recommended for Real-Time)
```bash
POST https://your-domain.com/api/v1/integrations/webhook/law_enforcement
Headers:
  X-Webhook-Secret: your_webhook_secret
  Content-Type: application/json
Body:
{
  "event_type": "incident_created",
  "id": "12345",
  "title": "Traffic Accident",
  "location": "Main St",
  "date": "2025-01-20"
}
```

### Option 2: Push Data Endpoint
```bash
POST https://your-domain.com/api/v1/integrations/push
Headers:
  Content-Type: application/json
Body:
{
  "system": "law_enforcement",
  "mapping": "incidents_to_events",
  "data": {
    "id": "12345",
    "title": "Traffic Accident",
    ...
  }
}
```

## Data Processing Flow

1. **External system sends data** → Webhook/Push endpoint
2. **Data is cached** → `external_data_cache` table (status: `pending`)
3. **Mapping is applied** → Transforms external data to internal schema
4. **Data is inserted** → Into target tables (events, campaigns, etc.)
5. **Integration checkpoint updated** → Tracks sync status
6. **Audit logged** → All operations logged

## Configuration Required

Before external systems can push data:

1. **Register External System**:
   ```sql
   INSERT INTO `campaign_department_external_systems` 
   (system_name, display_name, system_type) 
   VALUES ('law_enforcement', 'Law Enforcement System', 'hybrid');
   ```

2. **Configure Connection**:
   ```sql
   INSERT INTO `campaign_department_external_system_connections`
   (system_id, connection_type, api_base_url, api_auth_type, config_json)
   VALUES (1, 'api', 'https://law-enforcement-api.example.com', 'bearer', 
   '{"webhook_secret": "your_secret_here"}');
   ```

3. **Create Data Mapping**:
   ```sql
   INSERT INTO `campaign_department_external_data_mappings`
   (system_id, mapping_name, target_table, mapping_config, sync_frequency)
   VALUES (1, 'incidents_to_events', 'campaign_department_events', 
   '{"field_mappings": {"title": "event_title", "date": "date"}}', 'realtime');
   ```

4. **Grant Module Access**:
   ```sql
   INSERT INTO `campaign_department_module_system_mappings`
   (module_name, system_id, access_type)
   VALUES ('events', 1, 'read_write');
   ```

## Summary

✅ **Database schema**: Ready for real-time inserts  
✅ **Integration tables**: Ready for external data  
✅ **Pull mechanism**: Ready (query external systems)  
✅ **Push mechanism**: Ready (webhook + push endpoints)  
✅ **Data caching**: Ready (external_data_cache)  
✅ **Audit logging**: Ready (integration_query_logs)  
✅ **Access control**: Ready (module_system_mappings)

**All SQL migrations are ready for real-time data input and integration with external submodules.**






