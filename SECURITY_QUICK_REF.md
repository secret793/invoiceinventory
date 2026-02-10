# 🛡️ SECURITY QUICK REFERENCE

## 🚀 IMMEDIATE SETUP (On VPS)

```bash
# 1. Pull latest security code
cd /var/www/invoiceinventory
sudo git pull origin master

# 2. Run one-time security setup
sudo bash setup_security.sh

# 3. Update email addresses
sudo nano /etc/fail2ban/jail.local  # Change destemail
sudo nano daily_security_check.sh   # Change EMAIL variable

# 4. Test the scanner
php security_scan.php

# 5. Clear caches
php artisan optimize:clear
sudo systemctl restart php8.3-fpm apache2
```

---

## 🔍 DAILY COMMANDS

```bash
# Check for malware
cd /var/www/invoiceinventory && php security_scan.php

# View security check results
tail -50 /var/log/security_check_$(date +%Y%m%d).log

# Check fail2ban status
sudo fail2ban-client status
sudo fail2ban-client status apache-auth

# Review logs
sudo tail -50 /var/log/apache2/error.log
sudo tail -50 /var/www/invoiceinventory/storage/logs/laravel.log
```

---

## 🛡️ WHAT'S PROTECTED NOW

### 1. **SecurityMiddleware** (Laravel)
✅ Blocks WordPress paths (wp-admin, wp-login, etc.)
✅ Blocks malicious user agents (nikto, sqlmap, etc.)
✅ Validates file uploads (blocks .php, .exe, etc.)
✅ Blocks SQL injection attempts
✅ Blocks XSS attacks
✅ Rate limiting (100 requests/minute per IP)
✅ Logs all suspicious activity

### 2. **Malware Scanner** (security_scan.php)
✅ Scans for 25+ malware signatures
✅ Checks file integrity (index.php, .env)
✅ Validates permissions
✅ Finds suspicious files (wp-*.php, hidden files)
✅ Outputs color-coded report

### 3. **Automated Monitoring** (daily_security_check.sh)
✅ Daily malware scans
✅ File modification tracking
✅ WordPress file detection
✅ Permission checks
✅ Failed login monitoring
✅ Apache error scanning
✅ Email alerts on issues

### 4. **Server Hardening** (setup_security.sh)
✅ Fail2Ban (blocks attackers after 3 attempts)
✅ UFW Firewall (only ports 22, 80, 443 open)
✅ PHP dangerous functions disabled
✅ ModSecurity WAF enabled
✅ AIDE file integrity monitoring
✅ Apache security headers
✅ SSH hardened

---

## 🚨 BLOCKED PATTERNS

The SecurityMiddleware automatically blocks:

### Paths:
- `/wp-admin`, `/wp-login`, `/wp-content`
- `/.env`, `/.git`, `/phpinfo`
- `/shell`, `/backdoor`, `/c99`
- `/../` (directory traversal)

### File Types (Upload):
- `.php`, `.phtml`, `.phar`
- `.exe`, `.sh`, `.bat`
- `.htaccess`, `.htpasswd`

### Malicious Code:
- `eval(`, `base64_decode`
- `exec(`, `system(`, `shell_exec(`
- `<script`, `javascript:`
- `union select`, `drop table`

---

## 📊 MONITORING DASHBOARD

Check these regularly:

```bash
# Fail2Ban banned IPs
sudo fail2ban-client status | grep "Currently banned"

# Recent security events
tail -100 /var/www/invoiceinventory/storage/logs/laravel.log | grep -i "blocked\|suspicious"

# Disk usage
df -h /

# Service status
sudo systemctl status fail2ban php8.3-fpm apache2

# Active connections
ss -tunap | grep :443 | wc -l
```

---

## 🔥 EMERGENCY RESPONSE

If malware detected:

```bash
# 1. Take site offline
sudo a2dissite etracking-ssl
sudo systemctl reload apache2

# 2. Kill PHP processes
sudo killall php-fpm8.3

# 3. Backup for forensics
sudo tar -czf /root/breach_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/invoiceinventory

# 4. Clean and restore
cd /var/www/invoiceinventory
sudo git reset --hard HEAD
sudo git clean -fd

# 5. Restore .env
sudo cp .env.backup .env

# 6. Fix permissions
sudo bash fix_deployment.sh

# 7. Bring site back
sudo a2ensite etracking-ssl
sudo systemctl reload apache2
```

---

## ✅ VERIFY PROTECTION

Test the middleware is working:

```bash
# Should get 404 (blocked)
curl https://etracking-gambia.gm/wp-admin

# Should get 404 (blocked)
curl https://etracking-gambia.gm/wp-login.php

# Should get 403 (blocked)
curl -A "nikto" https://etracking-gambia.gm/admin

# Should work normally
curl https://etracking-gambia.gm/admin
```

---

## 📧 ALERTS

You'll receive emails for:
- Malware detection
- Failed login attempts (3+)
- Suspicious file modifications
- WordPress files found
- Disk space >90%

Configure email in:
- `/etc/fail2ban/jail.local` → `destemail`
- `daily_security_check.sh` → `EMAIL` variable

---

## 🔐 PASSWORD ROTATION

Change these every 90 days:

```bash
# Database password
mysql -u root -p
ALTER USER 'etracking_user'@'localhost' IDENTIFIED BY 'NewPassword123!';
FLUSH PRIVILEGES;
exit;

# Update .env
sudo nano /var/www/invoiceinventory/.env
# Change DB_PASSWORD

# Clear cache
php artisan config:clear

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📚 MORE INFO

- Full guide: [SECURITY_PROTECTION_GUIDE.md](SECURITY_PROTECTION_GUIDE.md)
- Scanner: `security_scan.php`
- Automated check: `daily_security_check.sh`
- Setup script: `setup_security.sh`

---

## 🆘 SUPPORT COMMANDS

```bash
# Test Laravel
php artisan about

# Test database
php artisan migrate:status

# Check logs
php artisan log:clear  # If exists
tail -f storage/logs/laravel.log

# Performance
php artisan optimize
```
