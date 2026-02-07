#!/bin/bash
# Laravel Apache Routing Diagnostic Script
# Run this on your Ubuntu server as root or with sudo
# Usage: sudo bash diagnose_server.sh

echo "=========================================="
echo "🔍 Laravel Apache Routing Diagnostics"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check Apache is running
echo "1️⃣  Apache Service Status"
echo "----------------------------------------"
if systemctl is-active --quiet apache2; then
    echo -e "${GREEN}✅ Apache is running${NC}"
else
    echo -e "${RED}❌ Apache is NOT running${NC}"
    echo "   Run: sudo systemctl start apache2"
fi
echo ""

# Test 2: Check mod_rewrite
echo "2️⃣  mod_rewrite Module"
echo "----------------------------------------"
if apache2ctl -M 2>/dev/null | grep -q 'rewrite_module'; then
    echo -e "${GREEN}✅ mod_rewrite is enabled${NC}"
else
    echo -e "${RED}❌ mod_rewrite is NOT enabled${NC}"
    echo "   Run: sudo a2enmod rewrite && sudo systemctl restart apache2"
fi
echo ""

# Test 3: Check Virtual Host Configuration
echo "3️⃣  Virtual Host Configuration"
echo "----------------------------------------"
VHOST_FILE=$(grep -r "etracking-gambia.gm" /etc/apache2/sites-enabled/ 2>/dev/null | head -1 | cut -d':' -f1)

if [ -n "$VHOST_FILE" ]; then
    echo -e "${GREEN}✅ Virtual host found: $VHOST_FILE${NC}"
    echo ""
    echo "📄 Configuration:"
    cat "$VHOST_FILE"
    echo ""
    
    # Check DocumentRoot
    DOCROOT=$(grep -i "DocumentRoot" "$VHOST_FILE" | head -1 | awk '{print $2}')
    echo "📂 DocumentRoot: $DOCROOT"
    
    if [[ "$DOCROOT" == *"/public"* ]] || [[ "$DOCROOT" == *"/public" ]]; then
        echo -e "${GREEN}✅ DocumentRoot correctly points to /public${NC}"
    else
        echo -e "${RED}❌ DocumentRoot should end with /public${NC}"
        echo "   Current: $DOCROOT"
        echo "   Should be: /path/to/invoiceinventory/public"
    fi
    
    # Check AllowOverride
    if grep -q "AllowOverride All" "$VHOST_FILE"; then
        echo -e "${GREEN}✅ AllowOverride All is set${NC}"
    else
        echo -e "${RED}❌ AllowOverride All is NOT set${NC}"
        echo "   Add this to your <Directory> block:"
        echo "   AllowOverride All"
    fi
else
    echo -e "${YELLOW}⚠️  No virtual host found for etracking-gambia.gm${NC}"
    echo "   Check files in: /etc/apache2/sites-enabled/"
    ls -la /etc/apache2/sites-enabled/
fi
echo ""

# Test 4: Check global AllowOverride settings
echo "4️⃣  Global AllowOverride Settings"
echo "----------------------------------------"
if grep -r "AllowOverride None" /etc/apache2/apache2.conf | grep -v "^#"; then
    echo -e "${YELLOW}⚠️  Found 'AllowOverride None' in apache2.conf${NC}"
    echo "   This may override VirtualHost settings"
else
    echo -e "${GREEN}✅ No global 'AllowOverride None' found${NC}"
fi
echo ""

# Test 5: Check if project directory exists
echo "5️⃣  Project Directory Check"
echo "----------------------------------------"
# Try to find the project
POSSIBLE_PATHS=(
    "/var/www/html/invoiceinventory"
    "/var/www/invoiceinventory"
    "/home/*/invoiceinventory"
    "/opt/invoiceinventory"
)

PROJECT_PATH=""
for path in "${POSSIBLE_PATHS[@]}"; do
    if [ -d "$path" ] || [ -d "${path}/public" ]; then
        PROJECT_PATH=$(find / -name "invoiceinventory" -type d 2>/dev/null | grep -v "/vendor/" | head -1)
        break
    fi
done

if [ -n "$PROJECT_PATH" ]; then
    echo -e "${GREEN}✅ Project found at: $PROJECT_PATH${NC}"
    
    # Check public folder
    if [ -d "$PROJECT_PATH/public" ]; then
        echo -e "${GREEN}✅ public folder exists${NC}"
        
        # Check .htaccess
        if [ -f "$PROJECT_PATH/public/.htaccess" ]; then
            echo -e "${GREEN}✅ .htaccess exists in public folder${NC}"
            echo "   Permissions: $(stat -c '%a' $PROJECT_PATH/public/.htaccess)"
        else
            echo -e "${RED}❌ .htaccess NOT found in public folder${NC}"
        fi
        
        # Check index.php
        if [ -f "$PROJECT_PATH/public/index.php" ]; then
            echo -e "${GREEN}✅ index.php exists in public folder${NC}"
        else
            echo -e "${RED}❌ index.php NOT found in public folder${NC}"
        fi
    else
        echo -e "${RED}❌ public folder NOT found${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Project directory not found${NC}"
    echo "   Please specify the full path to your Laravel project"
    read -p "   Enter project path: " PROJECT_PATH
fi
echo ""

# Test 6: Check file permissions
echo "6️⃣  File Permissions"
echo "----------------------------------------"
if [ -n "$PROJECT_PATH" ] && [ -d "$PROJECT_PATH" ]; then
    echo "📂 Project ownership:"
    ls -ld "$PROJECT_PATH" | awk '{print $3":"$4}'
    
    echo ""
    echo "📂 Public folder ownership:"
    ls -ld "$PROJECT_PATH/public" 2>/dev/null | awk '{print $3":"$4}'
    
    echo ""
    echo "📄 Storage permissions:"
    if [ -d "$PROJECT_PATH/storage" ]; then
        ls -ld "$PROJECT_PATH/storage" | awk '{print "  "$1" "$3":"$4}'
        echo -e "${YELLOW}   Storage should be writable by web server${NC}"
    fi
    
    echo ""
    echo "📄 Bootstrap/cache permissions:"
    if [ -d "$PROJECT_PATH/bootstrap/cache" ]; then
        ls -ld "$PROJECT_PATH/bootstrap/cache" | awk '{print "  "$1" "$3":"$4}'
        echo -e "${YELLOW}   Bootstrap cache should be writable by web server${NC}"
    fi
fi
echo ""

# Test 7: Check PHP version
echo "7️⃣  PHP Version"
echo "----------------------------------------"
PHP_VERSION=$(php -v | head -1)
echo "$PHP_VERSION"
if php -v | grep -q "PHP 8"; then
    echo -e "${GREEN}✅ PHP 8.x detected - Good for Laravel 9+${NC}"
elif php -v | grep -q "PHP 7.[4-9]"; then
    echo -e "${YELLOW}⚠️  PHP 7.4+ - Compatible but consider upgrading${NC}"
else
    echo -e "${RED}❌ PHP version may be too old${NC}"
fi
echo ""

# Test 8: Check PHP modules
echo "8️⃣  Required PHP Modules"
echo "----------------------------------------"
REQUIRED_MODULES=("openssl" "pdo" "mbstring" "tokenizer" "xml" "ctype" "json" "bcmath")
for module in "${REQUIRED_MODULES[@]}"; do
    if php -m | grep -q "^$module$"; then
        echo -e "${GREEN}✅ $module${NC}"
    else
        echo -e "${RED}❌ $module - MISSING${NC}"
    fi
done
echo ""

# Test 9: Test .htaccess reading
echo "9️⃣  Test .htaccess Processing"
echo "----------------------------------------"
if [ -n "$PROJECT_PATH" ] && [ -f "$PROJECT_PATH/public/.htaccess" ]; then
    echo "📄 .htaccess content preview:"
    head -10 "$PROJECT_PATH/public/.htaccess"
    echo ""
    
    # Test if it's readable by Apache user
    APACHE_USER=$(ps aux | grep apache2 | grep -v grep | head -1 | awk '{print $1}')
    if [ -n "$APACHE_USER" ]; then
        echo "🔐 Apache runs as user: $APACHE_USER"
        if sudo -u "$APACHE_USER" test -r "$PROJECT_PATH/public/.htaccess"; then
            echo -e "${GREEN}✅ Apache can read .htaccess${NC}"
        else
            echo -e "${RED}❌ Apache CANNOT read .htaccess${NC}"
            echo "   Fix: sudo chmod 644 $PROJECT_PATH/public/.htaccess"
        fi
    fi
fi
echo ""

# Test 10: Apache Error Log
echo "🔟 Recent Apache Errors"
echo "----------------------------------------"
if [ -f "/var/log/apache2/error.log" ]; then
    echo "Last 10 error log entries:"
    tail -10 /var/log/apache2/error.log
else
    echo -e "${YELLOW}⚠️  Error log not found at default location${NC}"
fi
echo ""

# Summary
echo "=========================================="
echo "📋 SUMMARY & RECOMMENDATIONS"
echo "=========================================="
echo ""
echo "✅ Complete this checklist with your hosting provider:"
echo ""
echo "1. DocumentRoot points to: .../invoiceinventory/public"
echo "2. AllowOverride is set to: All"
echo "3. mod_rewrite is: Enabled"
echo "4. .htaccess file exists and is readable"
echo "5. File permissions allow Apache to read all files"
echo ""
echo "💡 Share this output with your hosting provider"
echo ""
echo "=========================================="
