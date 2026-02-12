# Troubleshooting Agency Coordination Errors

## Current Status
✅ Database columns added successfully:
- `campaign_department_events.post_event_notes` - EXISTS
- `campaign_department_events.last_updated` - EXISTS  
- `campaign_department_event_agency_coordination.action_type` - EXISTS

## If Errors Still Occur

### Step 1: Verify Database Schema
Run this script to confirm all columns exist:
```bash
php database/migrations/verify_columns.php
```

### Step 2: Clear PHP OpCache (Production Server)
If using PHP OpCache, restart PHP-FPM or Apache:

**For Apache:**
```bash
sudo systemctl restart apache2
```

**For PHP-FPM:**
```bash
sudo systemctl restart php-fpm
# or
sudo systemctl restart php8.1-fpm
```

### Step 3: Clear Browser Cache
1. Open browser DevTools (F12)
2. Right-click refresh button
3. Select "Empty Cache and Hard Reload"

### Step 4: Check Error Logs
View PHP error logs:
```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php-fpm/error.log
```

### Step 5: Test API Directly
Test the agency coordination endpoint:
```bash
curl -X POST http://your-domain/api/v1/events/5/agency-coordination \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"agency_type":"police","agency_name":"Test Agency","request_details":"Test"}'
```

## Common Issues

### Issue: "Unknown column 'action_type'"
**Cause:** Column doesn't exist in database
**Solution:** Run the migration again:
```sql
ALTER TABLE `campaign_department_event_agency_coordination` 
ADD COLUMN `action_type` VARCHAR(100) NULL;
```

### Issue: "Duplicate column name"
**Cause:** Column already exists (this is good!)
**Solution:** No action needed, column is already there

### Issue: Changes not reflecting
**Cause:** PHP OpCache or browser cache
**Solution:** 
1. Restart Apache/PHP-FPM
2. Clear browser cache
3. Try incognito/private browsing mode

## Verify Code is Latest
Check EventController.php line 636-641:
```php
$stmt = $this->pdo->prepare('
    INSERT INTO `campaign_department_event_agency_coordination` (
        event_id, agency_type, agency_name, status, request_details
    ) VALUES (
        :event_id, :agency_type, :agency_name, "pending", :request_details
    )
');
```

This should NOT include `action_type` in the INSERT statement.
