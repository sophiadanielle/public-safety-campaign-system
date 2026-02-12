# Production Deployment Steps

## Issue: Agency Coordination & Event Edit Errors

### Errors Fixed:
1. ❌ Submit Request: "Unknown column 'action_type'"
2. ❌ Save Notes: "Unknown column 'action_type'" (actually missing post_event_notes)
3. ❌ Edit Event: Form fields not populating

---

## Step 1: Pull Latest Code

SSH into your production server and run:

```bash
cd /var/www/html/safety_campaign_alertaraqc
git pull origin main
```

---

## Step 2: Run Database Migration

### Option A: Using PHP Script (Recommended)

1. Edit the database password in the migration file:
```bash
nano database/migrations/production_fix.php
```

2. Update this line with your production MySQL password:
```php
$password = 'your_production_password_here';
```

3. Run the migration:
```bash
php database/migrations/production_fix.php
```

### Option B: Using MySQL Command Line

```bash
mysql -u root -p LGU
```

Then paste these commands:

```sql
ALTER TABLE `campaign_department_events` 
ADD COLUMN `post_event_notes` TEXT NULL;

ALTER TABLE `campaign_department_events` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;
```

**Note:** If you get "Duplicate column name" errors, that's fine - it means the column already exists.

---

## Step 3: Verify the Fix

1. Go to your Events page
2. Try to **Submit Agency Coordination Request** - should work without errors
3. Try to **Save Post-Event Notes** - should work without errors
4. Try to **Edit an Event** - all fields should populate correctly

---

## What Was Fixed:

### Code Changes:
1. **EventController::show()** - Now returns all necessary columns with correct aliases
2. **EventController::update()** - Maps frontend field names to database column names

### Database Changes:
1. Added `post_event_notes` column to `campaign_department_events`
2. Added `last_updated` column to `campaign_department_events`
3. Added `action_type` column to `campaign_department_event_agency_coordination`

---

## Troubleshooting

### If migration fails:
1. Check MySQL is running: `systemctl status mysql`
2. Check database exists: `mysql -u root -p -e "SHOW DATABASES;"`
3. Check user permissions: `mysql -u root -p -e "SHOW GRANTS;"`

### If errors persist after migration:
1. Clear browser cache
2. Check browser console for JavaScript errors
3. Check PHP error logs: `tail -f /var/log/apache2/error.log`
