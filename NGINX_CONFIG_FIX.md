# Nginx Configuration Fix for API Routing

## Problem
API calls to `/index.php/api/v1/auth/login` are returning 404 from Nginx instead of being routed to PHP.

## Solution: Update Nginx Configuration

Your Nginx config needs to route all requests through `index.php`. Here's the fix:

### Option 1: Update Nginx Site Configuration

Edit your Nginx site config (usually in `/etc/nginx/sites-available/campaign.alertaraqc.com` or similar):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name campaign.alertaraqc.com;
    
    root /var/www/html/safety_campaign_alertaraqc;
    index index.php index.html;

    # Route all requests to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;  # Adjust version as needed
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Handle API routes specifically (routes /api/* to index.php)
    location ~ ^/index\.php(/.*)?$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;  # Adjust version as needed
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param REQUEST_URI $uri$is_args$args;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

### Option 2: Better Routing (Recommended)

Use this cleaner approach that routes `/api/*` directly to `index.php`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name campaign.alertaraqc.com;
    
    root /var/www/html/safety_campaign_alertaraqc;
    index index.php index.html;

    # Route API requests directly to index.php
    location ~ ^/api/ {
        rewrite ^/api/(.*)$ /index.php/api/$1 last;
    }

    # Route index.php requests
    location ~ ^/index\.php(/.*)?$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param REQUEST_URI $uri$is_args$args;
        include fastcgi_params;
    }

    # Route all other requests to index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Handle PHP files
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

## Steps to Apply

1. **Find your Nginx config file:**
   ```bash
   ls -la /etc/nginx/sites-available/ | grep campaign
   # OR
   ls -la /etc/nginx/sites-enabled/ | grep campaign
   ```

2. **Backup current config:**
   ```bash
   sudo cp /etc/nginx/sites-available/campaign.alertaraqc.com /etc/nginx/sites-available/campaign.alertaraqc.com.backup
   ```

3. **Edit the config:**
   ```bash
   sudo nano /etc/nginx/sites-available/campaign.alertaraqc.com
   # OR
   sudo vi /etc/nginx/sites-available/campaign.alertaraqc.com
   ```

4. **Test Nginx config:**
   ```bash
   sudo nginx -t
   ```

5. **Reload Nginx:**
   ```bash
   sudo systemctl reload nginx
   # OR
   sudo service nginx reload
   ```

## Quick Fix: Check Current Config

First, let's see what your current Nginx config looks like:

```bash
# Find the config file
sudo find /etc/nginx -name "*campaign*" -o -name "*alertaraqc*" 2>/dev/null

# View current config
sudo cat /etc/nginx/sites-available/campaign.alertaraqc.com
# OR
sudo cat /etc/nginx/sites-enabled/campaign.alertaraqc.com
```

## Alternative: Fix JavaScript API Path

If you can't modify Nginx config, we can change the JavaScript to call `/api/v1/auth/login` directly instead of `/index.php/api/v1/auth/login`. But the Nginx fix is better.

