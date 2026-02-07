# Quick SSH Fix Commands

Since you have SSH access, here are the exact commands to run:

## Step 1: Find Your Web Root

```bash
# Find where index.php is located
find /var/www -name "index.php" -type f 2>/dev/null | head -5

# OR check common locations
ls -la /var/www/html/
ls -la /var/www/
ls -la /home/*/public_html/ 2>/dev/null
```

Once you find it, set the path (example if it's `/var/www/html`):
```bash
WEBROOT="/var/www/html"
cd $WEBROOT
```

## Step 2: Backup Current Files

```bash
# Create backups (replace WEBROOT with your actual path)
cd $WEBROOT
cp index.php index.php.backup.$(date +%Y%m%d_%H%M%S)
cp header/includes/path_helper.php header/includes/path_helper.php.backup.$(date +%Y%m%d_%H%M%S)
```

## Step 3: Check Current Files (to see if they need updating)

```bash
# Check if index.php has the early detection code
head -30 index.php | grep "FORCE_PRODUCTION_BASEPATH"

# Check if path_helper.php has the global flag check
head -20 header/includes/path_helper.php | grep "FORCE_PRODUCTION_BASEPATH"
```

If these commands return nothing, the files need to be updated.

## Step 4: Upload Updated Files

### Option A: Using SCP from your local Windows machine

Open a NEW terminal on your Windows machine (keep SSH session open) and run:

```bash
# Replace WEBROOT with the path you found in Step 1
scp index.php root@72.60.209.226:/var/www/html/
scp header/includes/path_helper.php root@72.60.209.226:/var/www/html/header/includes/
```

### Option B: Edit files directly on server using nano/vi

```bash
# Edit index.php
nano index.php

# Add this at the very top (after <?php, before any other code):
# Look for line 7 and add the early detection code there
```

## Step 5: Clear PHP Cache (CRITICAL!)

```bash
# Find which PHP-FPM service is running
systemctl list-units | grep php

# Then reload the appropriate one (try these):
sudo service php8.3-fpm reload
# OR
sudo service php8.2-fpm reload
# OR
sudo service php8.1-fpm reload
# OR
sudo systemctl reload php8.3-fpm
# OR if using Apache:
sudo service apache2 reload
```

## Step 6: Verify Files Are Updated

```bash
# Check file modification time (should be recent)
ls -la index.php
ls -la header/includes/path_helper.php

# Check if the fix code exists
grep -n "FORCE_PRODUCTION_BASEPATH" index.php
grep -n "FORCE_PRODUCTION_BASEPATH" header/includes/path_helper.php
```

## Step 7: Check PHP Error Logs

```bash
# Check for our debug messages
tail -50 /var/log/php*-fpm.log | grep -i "production\|basepath\|EARLY"
# OR
tail -50 /var/log/apache2/error.log | grep -i "production\|basepath\|EARLY"
```

## Step 8: Test

1. Upload `verify_fix.php` to your web root
2. Visit: `https://campaign.alertaraqc.com/verify_fix.php`
3. It will show if the fix is working

## Quick One-Liner to Check Everything

```bash
# Run this to see current status
echo "=== Checking Files ===" && \
find /var/www -name "index.php" -type f 2>/dev/null | head -1 | xargs dirname | xargs -I {} sh -c 'cd {} && echo "Web root: {}" && echo "index.php has fix: $(head -30 index.php | grep -c FORCE_PRODUCTION_BASEPATH)" && echo "path_helper.php has fix: $(head -20 header/includes/path_helper.php 2>/dev/null | grep -c FORCE_PRODUCTION_BASEPATH || echo 0)"'
```

