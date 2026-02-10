# Fix 413 Request Entity Too Large Error

## Problem
Getting "413 Request Entity Too Large" when uploading campaign materials.

## Solution

Your server is blocking large file uploads. Follow the steps below based on your web server.

---

## For Nginx (Most Common on Ubuntu)

### Step 1: Edit Nginx Configuration

SSH into your server and edit the Nginx config:

```bash
ssh root@72.60.209.226
cd /etc/nginx/sites-available
nano default
```

### Step 2: Add/Update client_max_body_size

Find the `server` block and add this line:

```nginx
server {
    listen 80;
    server_name campaign.alertaraqc.com;
    
    # Add this line to allow 50MB uploads
    client_max_body_size 50M;
    
    root /var/www/html/safety_campaign_alertaraqc;
    index index.php index.html;
    
    # ... rest of your config
}
```

### Step 3: Test and Reload Nginx

```bash
# Test configuration
sudo nginx -t

# If test passes, reload Nginx
sudo systemctl reload nginx
```

---

## For Apache (If using Apache instead)

The `.htaccess` file has already been updated with:
- `LimitRequestBody 52428800` (50MB limit)
- PHP upload limits increased

Just restart Apache:

```bash
sudo systemctl restart apache2
```

---

## For PHP-FPM (If using PHP-FPM with Nginx)

### Edit PHP-FPM pool configuration:

```bash
# Find your PHP version first
php -v

# Edit the pool config (replace 8.1 with your version)
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Add these lines at the end:

```ini
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M
php_admin_value[max_execution_time] = 300
php_admin_value[memory_limit] = 256M
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.1-fpm
```

---

## Quick Fix Command (Run on Server)

Copy and paste this entire block into your SSH terminal:

```bash
# Detect web server and apply fix
if systemctl is-active --quiet nginx; then
    echo "Detected Nginx - Applying fix..."
    
    # Backup current config
    sudo cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.backup
    
    # Add client_max_body_size if not present
    if ! grep -q "client_max_body_size" /etc/nginx/sites-available/default; then
        sudo sed -i '/server {/a \    client_max_body_size 50M;' /etc/nginx/sites-available/default
        echo "Added client_max_body_size to Nginx config"
    else
        echo "client_max_body_size already exists in config"
    fi
    
    # Test and reload
    sudo nginx -t && sudo systemctl reload nginx
    echo "✓ Nginx reloaded successfully"
    
elif systemctl is-active --quiet apache2; then
    echo "Detected Apache - Restarting..."
    sudo systemctl restart apache2
    echo "✓ Apache restarted successfully"
else
    echo "Could not detect web server. Please configure manually."
fi

# Also restart PHP-FPM if present
if systemctl is-active --quiet php*-fpm; then
    sudo systemctl restart php*-fpm
    echo "✓ PHP-FPM restarted"
fi

echo ""
echo "Upload limit fix applied! Try uploading again."
```

---

## Verify the Fix

After applying the fix, test by uploading a file:

1. Go to Content Repository page
2. Try uploading a campaign material (up to 50MB)
3. Should work without 413 error

---

## Troubleshooting

### Still getting 413 error?

1. **Check if Nginx is running:**
   ```bash
   systemctl status nginx
   ```

2. **Check Nginx error log:**
   ```bash
   sudo tail -f /var/log/nginx/error.log
   ```

3. **Verify the setting was applied:**
   ```bash
   sudo nginx -T | grep client_max_body_size
   ```

4. **Try increasing the limit further:**
   Edit `/etc/nginx/sites-available/default` and change:
   ```nginx
   client_max_body_size 100M;
   ```

### Check current PHP limits:

```bash
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"
```
