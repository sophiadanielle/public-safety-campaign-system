# Migration Files - campaign_department_ Prefix Update Complete

## ✅ All Migration Files Updated

All migration files have been updated to use the `campaign_department_` prefix for table names, matching the pattern of `crime_department_` tables in your database.

## Updated Files

### Core Foundation
1. ✅ `001_initial_schema.sql` - All base tables
2. ✅ `011_complete_schema_update.sql` - All ALTER TABLE, INSERT, SELECT, VIEW statements
3. ✅ `012_seed_data.sql` - All INSERT statements
4. ✅ `013_seed_qc_reference_data.sql` - All INSERT statements

### Content & Campaigns
5. ✅ `004_content_extensions.sql` - ALTER TABLE statements
6. ✅ `009_links_and_enrichment.sql` - ALTER TABLE statements
7. ✅ `010_campaign_planning_fields.sql` - ALTER TABLE statements
8. ✅ `014_content_repository.sql` - CREATE TABLE, ALTER TABLE statements
9. ✅ `015_content_repository_seed.sql` - INSERT statements

### Segments & Audience
10. ✅ `005_sample_audience_members.sql` - INSERT statements
11. ✅ `016_segments_module_update.sql` - ALTER TABLE, CREATE VIEW statements

### Events Module
12. ✅ `017_events_module_enhancement.sql` - ALTER TABLE, CREATE TABLE statements
13. ✅ `025_events_module_complete_requirements.sql` - CREATE/ALTER TABLE, CREATE VIEW statements

### Additional Features
14. ✅ `006_survey_status.sql` - ALTER TABLE statements
15. ✅ `007_evaluation_reports.sql` - CREATE TABLE statements
16. ✅ `008_schedule_status.sql` - ALTER TABLE statements
17. ✅ `018_notifications_system.sql` - CREATE TABLE statements
18. ✅ `019_messaging_system.sql` - CREATE TABLE statements
19. ✅ `020_automl_integration.sql` - CREATE TABLE statements

### Integration System
20. ✅ `026_external_system_integration.sql` - CREATE TABLE, INSERT, CREATE VIEW statements

## Table Name Mapping

All tables now use the `campaign_department_` prefix:

| Original Name | Prefixed Name |
|--------------|---------------|
| roles | campaign_department_roles |
| permissions | campaign_department_permissions |
| role_permissions | campaign_department_role_permissions |
| barangays | campaign_department_barangays |
| users | campaign_department_users |
| campaigns | campaign_department_campaigns |
| campaign_schedules | campaign_department_campaign_schedules |
| content_items | campaign_department_content_items |
| attachments | campaign_department_attachments |
| audience_segments | campaign_department_audience_segments |
| audience_members | campaign_department_audience_members |
| campaign_audience | campaign_department_campaign_audience |
| events | campaign_department_events |
| attendance | campaign_department_attendance |
| surveys | campaign_department_surveys |
| survey_questions | campaign_department_survey_questions |
| survey_responses | campaign_department_survey_responses |
| feedback | campaign_department_feedback |
| impact_metrics | campaign_department_impact_metrics |
| partners | campaign_department_partners |
| partner_engagements | campaign_department_partner_engagements |
| automl_predictions | campaign_department_automl_predictions |
| integration_logs | campaign_department_integration_logs |
| notification_logs | campaign_department_notification_logs |
| audit_logs | campaign_department_audit_logs |
| tags | campaign_department_tags |
| content_usage | campaign_department_content_usage |
| external_systems | campaign_department_external_systems |
| external_system_connections | campaign_department_external_system_connections |
| external_data_mappings | campaign_department_external_data_mappings |
| external_data_cache | campaign_department_external_data_cache |
| integration_query_logs | campaign_department_integration_query_logs |
| module_system_mappings | campaign_department_module_system_mappings |
| event_agency_coordination | campaign_department_event_agency_coordination |
| event_conflicts | campaign_department_event_conflicts |
| event_audit_log | campaign_department_event_audit_log |
| event_integration_checkpoints | campaign_department_event_integration_checkpoints |
| event_facilitators | campaign_department_event_facilitators |
| event_audience_segments | campaign_department_event_audience_segments |
| event_partners | campaign_department_event_partners |
| event_logistics | campaign_department_event_logistics |
| content_item_versions | campaign_department_content_item_versions |
| campaign_content_items | campaign_department_campaign_content_items |
| content_tags | campaign_department_content_tags |
| evaluation_reports | campaign_department_evaluation_reports |
| notifications | campaign_department_notifications |
| conversations | campaign_department_conversations |
| messages | campaign_department_messages |
| ai_model_versions | campaign_department_ai_model_versions |
| ai_training_logs | campaign_department_ai_training_logs |
| ai_prediction_cache | campaign_department_ai_prediction_cache |
| ai_prediction_requests | campaign_department_ai_prediction_requests |
| reference_locations | campaign_department_reference_locations |
| reference_staff | campaign_department_reference_staff |

## Import Order

Import these files in this exact order:

1. `001_initial_schema.sql` ✅
2. `011_complete_schema_update.sql` ✅
3. `012_seed_data.sql` ✅
4. `013_seed_qc_reference_data.sql` ✅
5. `004_content_extensions.sql` ✅
6. `005_sample_audience_members.sql` ✅
7. `006_survey_status.sql` ✅
8. `007_evaluation_reports.sql` ✅
9. `008_schedule_status.sql` ✅
10. `009_links_and_enrichment.sql` ✅
11. `010_campaign_planning_fields.sql` ✅
12. `014_content_repository.sql` ✅
13. `015_content_repository_seed.sql` ✅
14. `016_segments_module_update.sql` ✅
15. `017_events_module_enhancement.sql` ✅
16. `018_notifications_system.sql` ✅
17. `019_messaging_system.sql` ✅
18. `020_automl_integration.sql` ✅
19. `025_events_module_complete_requirements.sql` ✅
20. `026_external_system_integration.sql` ✅

## Result

After importing all files, your LGU database will have:
- `crime_department_*` tables (existing)
- `campaign_department_*` tables (new)

All tables are clearly identified by their department prefix, making it easy to distinguish between different subsystems in the same database.



