#!/bin/bash
# Production Fix Commands for SSH
# Run these commands one by one on your server

echo "=== Step 1: Find your web root directory ==="
echo "Checking common locations..."

# Check common web root locations
if [ -d "/var/www/html" ]; then
    echo "Found: /var/www/html"
    WEBROOT="/var/www/html"
elif [ -d "/var/www" ]; then
    echo "Found: /var/www"
    WEBROOT="/var/www"
elif [ -d "/home" ]; then
    echo "Checking /home directories..."
    # Look for common user directories
    for dir in /home/*/public_html /home/*/www /home/*/html; do
        if [ -d "$dir" ]; then
            echo "Found: $dir"
            WEBROOT="$dir"
            break
        fi
    done
else
    echo "Please find your web root manually:"
    echo "  find / -name 'index.php' -type f 2>/dev/null | grep -v '/proc\|/sys' | head -5"
fi

echo ""
echo "=== Step 2: Backup current files ==="
echo "cd $WEBROOT"
echo "cp index.php index.php.backup.$(date +%Y%m%d_%H%M%S)"
echo "cp header/includes/path_helper.php header/includes/path_helper.php.backup.$(date +%Y%m%d_%H%M%S)"

echo ""
echo "=== Step 3: Check current file contents ==="
echo "# Check if early detection exists in index.php:"
echo "head -30 $WEBROOT/index.php | grep -i 'FORCE_PRODUCTION'"

echo ""
echo "# Check if global flag exists in path_helper.php:"
echo "head -20 $WEBROOT/header/includes/path_helper.php | grep -i 'FORCE_PRODUCTION'"

echo ""
echo "=== Step 4: Clear PHP cache ==="
echo "# Try these commands (one should work):"
echo "sudo service php8.3-fpm reload"
echo "sudo service php8.2-fpm reload"
echo "sudo service php8.1-fpm reload"
echo "sudo service php-fpm reload"
echo "# OR"
echo "sudo systemctl reload php8.3-fpm"
echo "sudo systemctl reload php8.2-fpm"
echo "# OR if using Apache:"
echo "sudo service apache2 reload"

echo ""
echo "=== Step 5: Check PHP version and service ==="
echo "php -v"
echo "systemctl list-units | grep php"
echo "systemctl list-units | grep apache"

echo ""
echo "=== Step 6: Verify fix is working ==="
echo "# After uploading files, check:"
echo "tail -20 /var/log/php*-fpm.log | grep -i 'production\|basepath'"
echo "# OR"
echo "tail -20 /var/log/apache2/error.log | grep -i 'production\|basepath'"

