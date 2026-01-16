# Migration Files with campaign_department_ Prefix

All migration files have been updated to use the `campaign_department_` prefix for table names, similar to how `crime_department_` tables are named in your database.

## Updated Table Names

All tables now have the `campaign_department_` prefix. For example:
- `users` → `campaign_department_users`
- `campaigns` → `campaign_department_campaigns`
- `events` → `campaign_department_events`
- etc.

## Migration Files Status

### ✅ Completed
- `001_initial_schema.sql` - All base tables updated

### ⚠️ Need Manual Update
The following migration files also need to be updated to use the prefixed table names:
- `011_complete_schema_update.sql` - ALTER TABLE statements
- `012_seed_data.sql` - INSERT statements
- `013_seed_qc_reference_data.sql` - INSERT statements
- `014_content_repository.sql` - CREATE TABLE statements
- `015_content_repository_seed.sql` - INSERT statements
- `016_segments_module_update.sql` - ALTER TABLE statements
- `017_events_module_enhancement.sql` - ALTER TABLE statements
- `025_events_module_complete_requirements.sql` - CREATE/ALTER TABLE statements
- `026_external_system_integration.sql` - CREATE TABLE statements
- All other migration files with table references

## How to Update Remaining Files

For each migration file, replace:
- `ALTER TABLE campaigns` → `ALTER TABLE \`campaign_department_campaigns\``
- `INSERT INTO users` → `INSERT INTO \`campaign_department_users\``
- `FROM events` → `FROM \`campaign_department_events\``
- `REFERENCES campaigns(id)` → `REFERENCES \`campaign_department_campaigns\`(id)`

## Important Notes

1. **Foreign Key References**: All foreign key REFERENCES must use the prefixed table names
2. **JOIN Statements**: All JOIN clauses must use prefixed table names
3. **Views**: Any views created must reference prefixed table names
4. **Backup**: Original files are preserved (if using the script)

## Quick Reference: Table Name Mappings

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
| impact_metrics | campaign_department_impact_metrics |
| partners | campaign_department_partners |
| partner_engagements | campaign_department_partner_engagements |
| automl_predictions | campaign_department_automl_predictions |
| integration_logs | campaign_department_integration_logs |
| notification_logs | campaign_department_notification_logs |
| audit_logs | campaign_department_audit_logs |
| tags | campaign_department_tags |
| content_usage | campaign_department_content_usage |




