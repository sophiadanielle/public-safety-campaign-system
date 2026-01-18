# Migration Files Prefix Update Status

## ✅ Completed Files (Updated with `campaign_department_` prefix)

1. **001_initial_schema.sql** - ✅ All tables updated
2. **011_complete_schema_update.sql** - ✅ All ALTER TABLE, INSERT, SELECT, VIEW statements updated
3. **012_seed_data.sql** - ✅ All INSERT statements updated
4. **025_events_module_complete_requirements.sql** - ✅ All CREATE/ALTER TABLE statements updated
5. **026_external_system_integration.sql** - ✅ All CREATE TABLE, INSERT, VIEW statements updated

## ⚠️ Remaining Files (Need Update)

The following files still need to be updated with the `campaign_department_` prefix:

- `013_seed_qc_reference_data.sql` - INSERT statements
- `014_content_repository.sql` - CREATE TABLE statements
- `015_content_repository_seed.sql` - INSERT statements
- `016_segments_module_update.sql` - ALTER TABLE statements
- `017_events_module_enhancement.sql` - ALTER TABLE statements
- `004_content_extensions.sql` - ALTER TABLE statements
- `005_sample_audience_members.sql` - INSERT statements
- `006_survey_status.sql` - ALTER TABLE statements
- `007_evaluation_reports.sql` - CREATE TABLE statements
- `008_schedule_status.sql` - ALTER TABLE statements
- `009_links_and_enrichment.sql` - CREATE TABLE statements
- `010_campaign_planning_fields.sql` - ALTER TABLE statements
- `018_notifications_system.sql` - CREATE TABLE statements
- `019_messaging_system.sql` - CREATE TABLE statements
- `020_automl_integration.sql` - CREATE TABLE statements

## Quick Update Guide

For each remaining file, replace:
- `campaigns` → `campaign_department_campaigns`
- `users` → `campaign_department_users`
- `events` → `campaign_department_events`
- `content_items` → `campaign_department_content_items`
- `audience_segments` → `campaign_department_audience_segments`
- `surveys` → `campaign_department_surveys`
- etc.

## Import Order (All with Prefix)

1. `001_initial_schema.sql` ✅
2. `011_complete_schema_update.sql` ✅
3. `012_seed_data.sql` ✅
4. `013_seed_qc_reference_data.sql` ⚠️
5. `014_content_repository.sql` ⚠️
6. `015_content_repository_seed.sql` ⚠️
7. `016_segments_module_update.sql` ⚠️
8. `017_events_module_enhancement.sql` ⚠️
9. `025_events_module_complete_requirements.sql` ✅
10. `026_external_system_integration.sql` ✅
11. Other files as needed ⚠️

## Important Notes

- All tables will be created with `campaign_department_` prefix
- This matches the pattern of `crime_department_` tables in your database
- Foreign key references must also use prefixed table names
- Views must reference prefixed table names





