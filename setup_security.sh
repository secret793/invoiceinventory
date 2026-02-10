#!/bin/bash

###############################################################################
# SECURITY HARDENING SETUP SCRIPT
# Run this once to apply all security measures
# Usage: sudo bash setup_security.sh
###############################################################################

set -e  # Exit on error

echo "====================================================================="
echo "          LARAVEL APPLICATION SECURITY HARDENING"
echo "                    $(date)"
echo "====================================================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root: sudo bash setup_security.sh"
    exit 1
fi

APP_DIR="/var/www/invoiceinventory"
PHP_VERSION="8.3"

# Confirmation
read -p "This will harden security on your server. Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 0
fi

echo ""
echo "[1/10] Installing security packages..."
echo "---------------------------------------------------------------------"
apt update
apt install -y fail2ban ufw aide rkhunter logwatch mailutils inotify-tools

echo ""
echo "[2/10] Configuring Fail2Ban..."
echo "---------------------------------------------------------------------"
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
destemail = your@email.com
sendername = Fail2Ban

[sshd]
enabled = true
maxretry = 3

[apache-auth]
enabled = true

[apache-badbots]
enabled = true

[apache-noscript]
enabled = true
port = http,https
filter = apache-noscript
logpath = /var/log/apache2/*error.log
maxretry = 3
EOF

systemctl enable fail2ban
systemctl restart fail2ban
echo "✅ Fail2Ban configured and started"

echo ""
echo "[3/10] Configuring UFW Firewall..."
echo "---------------------------------------------------------------------"
ufw --force default deny incoming
ufw --force default allow outgoing
ufw --force allow 22/tcp   # SSH
ufw --force allow 80/tcp   # HTTP
ufw --force allow 443/tcp  # HTTPS
ufw --force enable
echo "✅ UFW Firewall enabled"

echo ""
echo "[4/10] Hardening PHP configuration..."
echo "---------------------------------------------------------------------"

# Disable dangerous functions in php.ini files
for ini in /etc/php/$PHP_VERSION/fpm/php.ini /etc/php/$PHP_VERSION/apache2/php.ini /etc/php/$PHP_VERSION/cli/php.ini; do
    if [ -f "$ini" ]; then
        # Backup original
        cp "$ini" "${ini}.backup"
        
        # Add/update disable_functions
        if grep -q "^disable_functions" "$ini"; then
            sed -i 's/^disable_functions.*/disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source/' "$ini"
        else
            echo "disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source" >> "$ini"
        fi
        
        # Other security settings
        sed -i 's/^expose_php.*/expose_php = Off/' "$ini"
        sed -i 's/^display_errors.*/display_errors = Off/' "$ini"
        
        echo "✅ Hardened $ini"
    fi
done

systemctl restart php${PHP_VERSION}-fpm 2>/dev/null || true
systemctl restart apache2
echo "✅ PHP hardened and restarted"

echo ""
echo "[5/10] Installing ModSecurity (Web Application Firewall)..."
echo "---------------------------------------------------------------------"
apt install -y libapache2-mod-security2
a2enmod security2 headers

# Configure ModSecurity
if [ -f /etc/modsecurity/modsecurity.conf-recommended ]; then
    cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf
    sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf
    echo "✅ ModSecurity enabled"
else
    echo "⚠️  ModSecurity config not found, skipping"
fi

systemctl restart apache2

echo ""
echo "[6/10] Setting up file permissions..."
echo "---------------------------------------------------------------------"
cd "$APP_DIR" || exit

chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
chmod 644 .env 2>/dev/null || echo "⚠️  .env not found"

# Prevent PHP execution in uploads
cat > public/storage/.htaccess << 'EOF'
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>
EOF

echo "✅ Permissions set correctly"

echo ""
echo "[7/10] Initializing AIDE (File Integrity Monitor)..."
echo "---------------------------------------------------------------------"
aideinit
if [ -f /var/lib/aide/aide.db.new ]; then
    mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
fi

# Schedule daily AIDE checks
(crontab -l 2>/dev/null | grep -v aide; echo "0 3 * * * /usr/bin/aide --check") | crontab -
echo "✅ AIDE initialized and scheduled"

echo ""
echo "[8/10] Hardening Apache configuration..."
echo "---------------------------------------------------------------------"

# Add security headers to Apache config
APACHE_SECURITY_CONF="/etc/apache2/conf-available/security-headers.conf"
cat > "$APACHE_SECURITY_CONF" << 'EOF'
# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always unset X-Powered-By
    Header always unset Server
</IfModule>

# Disable directory listing
<Directory /var/www/*/public>
    Options -Indexes +FollowSymLinks
</Directory>

# Block access to sensitive files
<FilesMatch "^\.env|^\.git|^composer\.(json|lock)|^package\.json|^README\.md|^\.htaccess">
    Require all denied
</FilesMatch>
EOF

a2enconf security-headers
a2enmod headers
systemctl reload apache2
echo "✅ Apache security headers configured"

echo ""
echo "[9/10] Setting up automated security scans..."
echo "---------------------------------------------------------------------"

# Make scripts executable
chmod +x "$APP_DIR/daily_security_check.sh"

# Schedule daily security scan
(crontab -l 2>/dev/null | grep -v security_check; echo "0 2 * * * cd $APP_DIR && bash daily_security_check.sh") | crontab -

# Schedule weekly full scan
(crontab -l 2>/dev/null | grep -v security_scan; echo "0 3 * * 0 cd $APP_DIR && php security_scan.php > /var/log/security_scan_weekly.log 2>&1") | crontab -

echo "✅ Automated scans scheduled"

echo ""
echo "[10/10] Hardening SSH..."
echo "---------------------------------------------------------------------"

SSH_CONFIG="/etc/ssh/sshd_config"
cp "$SSH_CONFIG" "${SSH_CONFIG}.backup"

# Harden SSH
sed -i 's/^#*PermitRootLogin.*/PermitRootLogin no/' "$SSH_CONFIG"
sed -i 's/^#*PasswordAuthentication.*/PasswordAuthentication no/' "$SSH_CONFIG"
sed -i 's/^#*PubkeyAuthentication.*/PubkeyAuthentication yes/' "$SSH_CONFIG"
sed -i 's/^#*X11Forwarding.*/X11Forwarding no/' "$SSH_CONFIG"

echo "⚠️  SSH hardened (root login disabled, password auth disabled)"
echo "⚠️  Make sure you have SSH keys set up before logging out!"
echo ""
read -p "Restart SSH service? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    systemctl restart sshd
    echo "✅ SSH restarted"
else
    echo "⚠️  SSH not restarted - changes will apply on next restart"
fi

echo ""
echo "====================================================================="
echo "                    SECURITY HARDENING COMPLETE"
echo "====================================================================="
echo ""
echo "✅ Security measures applied:"
echo "   - Fail2Ban installed and configured"
echo "   - UFW firewall enabled"
echo "   - PHP dangerous functions disabled"
echo "   - ModSecurity WAF enabled"
echo "   - File permissions secured"
echo "   - AIDE file integrity monitoring active"
echo "   - Apache security headers configured"
echo "   - Automated security scans scheduled"
echo "   - SSH hardened"
echo ""
echo "📋 Next steps:"
echo "   1. Update email in /etc/fail2ban/jail.local"
echo "   2. Update email in daily_security_check.sh"
echo "   3. Test SecurityMiddleware: cd $APP_DIR && php artisan route:list"
echo "   4. Run first scan: cd $APP_DIR && php security_scan.php"
echo "   5. Review cron jobs: crontab -l"
echo "   6. Change all passwords (database, admin users)"
echo ""
echo "🔍 Check status:"
echo "   - Fail2Ban: sudo fail2ban-client status"
echo "   - UFW: sudo ufw status"
echo "   - AIDE: sudo aide --check"
echo "   - Logs: tail -f /var/log/security_check*.log"
echo ""
echo "====================================================================="
