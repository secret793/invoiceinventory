#!/bin/bash

################################################################################
#                   DEPLOYMENT FIX SCRIPT
# Fixes all issues detected by health_check.php
# Usage: sudo bash fix_deployment.sh
################################################################################

set -e  # Exit on error

echo ""
echo "================================================================================"
echo "                    DEPLOYMENT FIX SCRIPT"
echo "                    Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================================================================"
echo ""

APP_PATH="/var/www/invoiceinventory"
WEB_USER="www-data"
WEB_GROUP="www-data"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}[1/6] FIXING FILE OWNERSHIP${NC}"
echo "------------------------------------------------------------------------"
echo "Setting all files to own: $WEB_USER:$WEB_GROUP"
sudo chown -R $WEB_USER:$WEB_GROUP "$APP_PATH"
echo -e "${GREEN}✅ Ownership fixed${NC}"
echo ""

echo -e "${YELLOW}[2/6] FIXING FILE PERMISSIONS${NC}"
echo "------------------------------------------------------------------------"
echo "Setting directory permissions to 775 (rwxrwxr-x)"
sudo chmod -R 775 "$APP_PATH/storage"
sudo chmod -R 775 "$APP_PATH/bootstrap/cache"
sudo chmod -R 775 "$APP_PATH/public"
sudo chmod 755 "$APP_PATH"

echo "Setting .env to 644 (rw-r--r--)"
sudo chmod 644 "$APP_PATH/.env"

echo "Setting vendor and node_modules to 755 (readable, not writable)"
sudo chmod -R 755 "$APP_PATH/vendor" 2>/dev/null || true
sudo chmod -R 755 "$APP_PATH/node_modules" 2>/dev/null || true

echo -e "${GREEN}✅ Permissions fixed${NC}"
echo ""

echo -e "${YELLOW}[3/6] VERIFYING .ENV FILE${NC}"
echo "------------------------------------------------------------------------"

# Check if .env exists
if [ ! -f "$APP_PATH/.env" ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    exit 1
fi

echo "✅ .env file exists"

# Show first 10 lines to verify structure
echo ""
echo "First 10 lines of .env:"
head -n 10 "$APP_PATH/.env"
echo ""

# Validate .env has no obvious syntax errors in critical lines
if ! grep -q "^DB_HOST=" "$APP_PATH/.env"; then
    echo -e "${RED}❌ DB_HOST not found in .env${NC}"
    exit 1
fi

if ! grep -q "^DB_DATABASE=" "$APP_PATH/.env"; then
    echo -e "${RED}❌ DB_DATABASE not found in .env${NC}"
    exit 1
fi

if ! grep -q "^DB_USERNAME=" "$APP_PATH/.env"; then
    echo -e "${RED}❌ DB_USERNAME not found in .env${NC}"
    exit 1
fi

if ! grep -q "^APP_KEY=" "$APP_PATH/.env"; then
    echo -e "${RED}❌ APP_KEY not found in .env${NC}"
    exit 1
fi

echo -e "${GREEN}✅ .env file structure valid${NC}"
echo ""

echo -e "${YELLOW}[4/6] VERIFYING PUBLIC/INDEX.PHP${NC}"
echo "------------------------------------------------------------------------"

if [ ! -f "$APP_PATH/public/index.php" ]; then
    echo -e "${RED}❌ public/index.php not found!${NC}"
    exit 1
fi

echo "First 10 lines of public/index.php:"
head -n 10 "$APP_PATH/public/index.php"
echo ""

# Check for Laravel content
if grep -q "bootstrap/app" "$APP_PATH/public/index.php"; then
    echo -e "${GREEN}✅ public/index.php is Laravel front controller${NC}"
else
    echo -e "${RED}❌ public/index.php does not contain 'bootstrap/app' - may not be correct!${NC}"
fi

# Verify it's not WordPress
if grep -q "wp-blog-header" "$APP_PATH/public/index.php"; then
    echo -e "${RED}❌ public/index.php contains WordPress code!${NC}"
    exit 1
fi

echo ""

echo -e "${YELLOW}[5/6] CLEARING LARAVEL CACHES & RESTARTING SERVICES${NC}"
echo "------------------------------------------------------------------------"

cd "$APP_PATH"

echo "Running php artisan optimize:clear..."
php artisan optimize:clear 2>&1 | grep -v "Command" || true

echo "Running php artisan config:clear..."
php artisan config:clear 2>&1 | grep -v "Command" || true

echo "Running php artisan cache:clear..."
php artisan cache:clear 2>&1 | grep -v "Command" || true

echo "Running php artisan view:clear..."
php artisan view:clear 2>&1 | grep -v "Command" || true

echo ""
echo "Restarting PHP-FPM..."
sudo systemctl restart php8.3-fpm

echo "Restarting Apache..."
sudo systemctl restart apache2

echo "Waiting for services to start..."
sleep 3

# Verify services are running
if systemctl is-active --quiet php8.3-fpm; then
    echo -e "${GREEN}✅ PHP-FPM is running${NC}"
else
    echo -e "${RED}❌ PHP-FPM failed to start${NC}"
fi

if systemctl is-active --quiet apache2; then
    echo -e "${GREEN}✅ Apache is running${NC}"
else
    echo -e "${RED}❌ Apache failed to start${NC}"
fi

echo ""

echo -e "${YELLOW}[6/6] RUNNING COMPREHENSIVE HEALTH CHECK${NC}"
echo "------------------------------------------------------------------------"
echo ""

cd "$APP_PATH"
php health_check.php

echo ""
echo "================================================================================"
echo "                    FIX SCRIPT COMPLETED"
echo "================================================================================"
echo ""
echo "NEXT STEPS:"
echo "  1. Test the application:"
echo "     curl -I https://etracking-gambia.gm --insecure"
echo ""
echo "  2. Check admin dashboard:"
echo "     https://etracking-gambia.gm/admin"
echo ""
echo "  3. Monitor logs in real-time:"
echo "     tail -f /var/www/invoiceinventory/storage/logs/laravel.log"
echo ""
echo "  4. Check Apache error log:"
echo "     tail -f /var/log/apache2/etracking-error.log"
echo ""
