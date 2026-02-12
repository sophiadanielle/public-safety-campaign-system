# Database Migration Guide

## Overview
This migration adds all missing tables and columns needed for the Event Management System to work properly.

---

## What This Migration Does

### 1. Adds Missing Columns to `campaign_department_events`
- `hazard_focus` - Type of hazard the event addresses
- `target_audience_profile_id` - Link to audience segment
- `transport_requirements` - Transportation needs
- `trainer_requirements` - Trainer/facilitator needs
- `equipment_requirements` - Equipment needs
- `volunteer_requirements` - Volunteer needs
- `attendance_count` - Number of attendees
- `created_by` - User who created the event
- `updated_at` - Last update timestamp

### 2. Creates New Tables
- `campaign_department_event_facilitators` - Event facilitators/trainers
- `campaign_department_event_audience_segments` - Event target audiences
- `campaign_department_event_agency_coordination` - Agency coordination requests
- `campaign_department_event_conflicts` - Event scheduling conflicts
- `campaign_department_event_audit_log` - Event change history
- `campaign_department_event_integration_checkpoints` - Integration tracking

### 3. Updates `campaign_department_attendance`
- Adds `participant_identifier` - Unique participant ID
- Adds `checkin_method` - How they checked in (QR, manual, online)
- Adds `checkin_notes` - Additional check-in notes

### 4. Adds Performance Indexes
- Indexes on commonly queried columns for faster searches

---

## How to Run the Migration

### Method 1: Via Browser (Recommended)

1. **Upload files to server:**
   ```bash
   ssh root@72.60.209.226
   cd /var/www/html/safety_campaign_alertaraqc
   git pull origin main
   ```

2. **Visit the migration runner:**
   ```
   https://campaign.alertaraqc.com/database/migrations/run_migration.php
   ```

3. **Check the output** - it will show:
   - ✓ Successful operations
   - ⚠ Skipped operations (already exist)
   - ✗ Errors (if any)

### Method 2: Via MySQL Command Line

1. **SSH into server:**
   ```bash
   ssh root@72.60.209.226
   ```

2. **Run the SQL file:**
   ```bash
   mysql -u your_username -p LGU < /var/www/html/safety_campaign_alertaraqc/database/migrations/fix_events_schema.sql
   ```

3. **Enter your MySQL password when prompted**

### Method 3: Via phpMyAdmin

1. Login to phpMyAdmin
2. Select the `LGU` database
3. Click "Import"
4. Choose `fix_events_schema.sql`
5. Click "Go"

---

## After Migration

Once the migration completes successfully:

### 1. Update EventController to Re-enable Features

The migration script will be committed, then you need to:

1. **Restore facilitators and segments code** in EventController
2. **Update events.php** to use proper API endpoints instead of direct endpoints
3. **Test all features:**
   - ✅ Create Event
   - ✅ View Event Details
   - ✅ Edit Event
   - ✅ Agency Coordination
   - ✅ Attendance Tracking
   - ✅ Export Reports

### 2. Remove Workaround Files (Optional)

After everything works with proper API:
- `public/test-events-direct.php` - can be removed
- `public/debug-events-api.php` - can be kept for debugging
- `public/check-db-schema.php` - can be kept for verification

---

## Rollback (If Needed)

If something goes wrong, you can rollback by:

```sql
-- Drop new tables
DROP TABLE IF EXISTS `campaign_department_event_integration_checkpoints`;
DROP TABLE IF EXISTS `campaign_department_event_audit_log`;
DROP TABLE IF EXISTS `campaign_department_event_conflicts`;
DROP TABLE IF EXISTS `campaign_department_event_agency_coordination`;
DROP TABLE IF EXISTS `campaign_department_event_audience_segments`;
DROP TABLE IF EXISTS `campaign_department_event_facilitators`;

-- Remove new columns from events table
ALTER TABLE `campaign_department_events`
DROP COLUMN IF EXISTS `updated_at`,
DROP COLUMN IF EXISTS `created_by`,
DROP COLUMN IF EXISTS `attendance_count`,
DROP COLUMN IF EXISTS `volunteer_requirements`,
DROP COLUMN IF EXISTS `equipment_requirements`,
DROP COLUMN IF EXISTS `trainer_requirements`,
DROP COLUMN IF EXISTS `transport_requirements`,
DROP COLUMN IF EXISTS `target_audience_profile_id`,
DROP COLUMN IF EXISTS `hazard_focus`;

-- Remove new columns from attendance table
ALTER TABLE `campaign_department_attendance`
DROP COLUMN IF EXISTS `checkin_notes`,
DROP COLUMN IF EXISTS `checkin_method`,
DROP COLUMN IF EXISTS `participant_identifier`;
```

---

## Troubleshooting

### Error: "Table already exists"
- **Solution:** This is normal. The migration skips existing tables.

### Error: "Column already exists"
- **Solution:** This is normal. The migration skips existing columns.

### Error: "Foreign key constraint fails"
- **Solution:** Make sure the referenced tables exist:
  - `campaign_department_users`
  - `campaign_department_audience_segments`
  - `campaign_department_campaigns`

### Error: "Access denied"
- **Solution:** Make sure your database user has ALTER and CREATE privileges.

---

## Verification

After migration, verify everything worked:

```sql
-- Check if tables exist
SHOW TABLES LIKE 'campaign_department_event%';

-- Check events table structure
DESCRIBE campaign_department_events;

-- Check attendance table structure
DESCRIBE campaign_department_attendance;

-- Count records in new tables
SELECT COUNT(*) FROM campaign_department_event_facilitators;
SELECT COUNT(*) FROM campaign_department_event_agency_coordination;
```

---

## Support

If you encounter issues:
1. Check the migration output for specific error messages
2. Verify database user permissions
3. Check that all referenced tables exist
4. Review the SQL file for syntax errors

---

**Migration Created:** February 12, 2026
**Version:** 1.0
**Database:** LGU
