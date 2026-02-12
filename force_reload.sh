#!/bin/bash
# Force PHP files to reload by touching them
# This updates the modification time, forcing Apache to reload

echo "Forcing PHP files to reload..."

# Touch all PHP files in src/Controllers
find /var/www/html/safety_campaign_alertaraqc/src/Controllers -name "*.php" -exec touch {} \;
echo "✓ Touched Controllers"

# Touch all PHP files in src/Routes  
find /var/www/html/safety_campaign_alertaraqc/src/Routes -name "*.php" -exec touch {} \;
echo "✓ Touched Routes"

# Touch all PHP files in public
find /var/www/html/safety_campaign_alertaraqc/public -name "*.php" -exec touch {} \;
echo "✓ Touched Public files"

echo "Done! PHP files should reload on next request."
echo "If issues persist, try: systemctl reload apache2"
