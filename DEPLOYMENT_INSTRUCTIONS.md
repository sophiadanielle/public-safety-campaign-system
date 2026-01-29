# Production Deployment Instructions

## Quick Fix: Update Files on Production Server

### Method 1: SSH/SCP (Recommended)

If you have SSH access to your production server:

```bash
# 1. Connect to your server
ssh user@your-server-ip

# 2. Navigate to your web root (usually one of these):
cd /var/www/html
# OR
cd /var/www/campaign.alertaraqc.com
# OR wherever your files are deployed

# 3. Backup current files (safety first)
cp index.php index.php.backup
cp header/includes/path_helper.php header/includes/path_helper.php.backup

# 4. Upload the updated files from your local machine:
# From your LOCAL machine, run:
scp index.php user@your-server:/path/to/your/webroot/
scp header/includes/path_helper.php user@your-server:/path/to/your/webroot/header/includes/

# 5. Clear PHP cache
sudo service php-fpm reload
# OR
sudo systemctl reload php-fpm
# OR if using Apache
sudo service apache2 reload
```

### Method 2: FTP/SFTP

1. Connect to your server using FileZilla, WinSCP, or similar
2. Navigate to your web root directory
3. Upload these files (overwrite existing):
   - `index.php`
   - `header/includes/path_helper.php`
   - `TEST_BASEPATH.php` (for testing)

### Method 3: Git (if using version control)

```bash
# On production server
cd /path/to/your/webroot
git pull origin main
# OR
git pull origin master
```

## CRITICAL: Clear All Caches

After uploading files, you MUST clear caches:

### 1. PHP Opcode Cache (OPcache)

```bash
# Option A: Reload PHP-FPM
sudo service php-fpm reload

# Option B: Restart PHP-FPM
sudo service php-fpm restart

# Option C: Clear OPcache via PHP
php -r "opcache_reset();"

# Option D: If using Apache with mod_php
sudo service apache2 restart
```

### 2. Web Server Cache

```bash
# Nginx
sudo service nginx reload

# Apache
sudo service apache2 reload
```

### 3. Browser Cache

- Hard refresh: `Ctrl+Shift+R` (Windows/Linux) or `Cmd+Shift+R` (Mac)
- Or clear browser cache completely

## Verify the Fix

### Step 1: Test Diagnostic Script

Visit: `https://campaign.alertaraqc.com/TEST_BASEPATH.php`

This will show:
- What HTTP_HOST contains
- Whether production is detected
- What basePath is set to

### Step 2: Check Login Page

1. Visit: `https://campaign.alertaraqc.com/`
2. Open browser DevTools (F12)
3. Go to Console tab
4. Should see: `BASE PATH: ` (empty, NOT `/public-safety-campaign-system`)

### Step 3: View Page Source

1. Right-click → View Page Source
2. Search for: `FINAL_BASEPATH`
3. Should see: `<!-- FINAL_BASEPATH:  -->` (empty)

## If Still Not Working

### Check 1: Verify Files Are Updated

On production server, check file modification dates:

```bash
ls -la index.php
ls -la header/includes/path_helper.php
```

Should show recent timestamps (today's date).

### Check 2: Verify File Contents

```bash
# Check if early production detection is in index.php
head -30 index.php | grep "FORCE_PRODUCTION_BASEPATH"

# Check if global flag check is in path_helper.php
head -20 header/includes/path_helper.php | grep "FORCE_PRODUCTION_BASEPATH"
```

### Check 3: Check PHP Error Logs

```bash
# Common log locations:
tail -f /var/log/php-fpm/error.log
# OR
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/nginx/error.log

# Look for messages like:
# "EARLY PRODUCTION DETECTION"
# "PATH_HELPER: Using global FORCE_PRODUCTION_BASEPATH flag"
```

### Check 4: Test HTTP_HOST Value

Create a simple test file `test_host.php`:

```php
<?php
header('Content-Type: text/plain');
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
```

Visit: `https://campaign.alertaraqc.com/test_host.php`

This will show what the server is actually receiving.

## Common Issues

### Issue 1: Files Not Uploaded
**Symptom:** Still seeing old behavior
**Solution:** Verify files are actually uploaded and have correct permissions

### Issue 2: PHP Cache Not Cleared
**Symptom:** Changes not taking effect
**Solution:** Reload/restart PHP-FPM or Apache

### Issue 3: Wrong File Permissions
**Symptom:** Files uploaded but not readable
**Solution:** 
```bash
chmod 644 index.php
chmod 644 header/includes/path_helper.php
```

### Issue 4: Multiple PHP Versions
**Symptom:** Changes work locally but not on server
**Solution:** Make sure you're editing the correct PHP version's files

## Quick Verification Checklist

- [ ] Files uploaded to production server
- [ ] PHP-FPM/Apache reloaded/restarted
- [ ] Browser cache cleared (hard refresh)
- [ ] TEST_BASEPATH.php shows correct detection
- [ ] Console shows `BASE PATH: ` (empty)
- [ ] Page source shows `FINAL_BASEPATH: ` (empty)
- [ ] CSS files load correctly (no 404 errors)
- [ ] API calls work (no 404 errors)

## Need Help?

If still not working after all steps:
1. Run `TEST_BASEPATH.php` and share the output
2. Check PHP error logs and share relevant entries
3. Verify HTTP_HOST value using `test_host.php`

