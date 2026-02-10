# SECURITY PROTECTION GUIDE
**How to Prevent PHP Malware Injection Attacks**

---

## 🎯 What Happened?
Your server was compromised with WordPress malware injected into:
- `public/index.php` (obfuscated webshell)
- `storage/logs/` (wp-blog-header.php, wp-cron.php)
- `public/wp-content/`, `wp-includes/`, `wp-admin/` directories

---

## 🛡️ HOW TO PREVENT THIS

### 1. **Server Hardening (CRITICAL)**

#### A. Install Fail2Ban (Blocks Attackers)
```bash
sudo apt update
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Configure for Apache
sudo tee /etc/fail2ban/jail.local > /dev/null << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[apache-auth]
enabled = true

[apache-badbots]
enabled = true

[apache-noscript]
enabled = true

[sshd]
enabled = true
maxretry = 3
EOF

sudo systemctl restart fail2ban
```

#### B. Disable Dangerous PHP Functions
Edit `/etc/php/8.3/fpm/php.ini` and `/etc/php/8.3/apache2/php.ini`:
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,eval,assert
```

Restart services:
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart apache2
```

#### C. Set Up Firewall (UFW)
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

#### D. Install ModSecurity (Web Application Firewall)
```bash
sudo apt install libapache2-mod-security2 -y
sudo a2enmod security2
sudo cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf
sudo sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf
sudo systemctl restart apache2
```

---

### 2. **File Integrity Monitoring**

#### Install AIDE (Monitors File Changes)
```bash
sudo apt install aide -y
sudo aideinit
sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db

# Schedule daily checks
sudo crontab -e
# Add this line:
0 3 * * * /usr/bin/aide --check | mail -s "AIDE Report" your@email.com
```

---

### 3. **Use the Security Tools Created**

#### A. Security Scanner (Run Weekly)
```bash
cd /var/www/invoiceinventory
php security_scan.php
```

Create a cron job:
```bash
sudo crontab -e
# Add:
0 2 * * 0 cd /var/www/invoiceinventory && php security_scan.php > /var/log/security_scan.log 2>&1
```

#### B. Security Middleware (Already Created)
Register the middleware in `bootstrap/app.php`:

```php
<?php

use App\Http\Middleware\SecurityMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Apply security middleware to all routes
        $middleware->append(SecurityMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

### 4. **Git Protection**

#### Add to `.gitignore`:
```gitignore
# Security - Never commit these
.env
.env.*
*.pem
*.key
id_rsa
id_dsa

# Prevent accidental malware commits
**/wp-*.php
**/shell*.php
**/c99*.php
**/backdoor*.php
```

#### Create Pre-commit Hook:
```bash
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
# Scan for malware before commit

echo "Running security scan..."

malware_patterns=(
    "eval("
    "base64_decode"
    "wp-blog-header"
    "wp-cron"
    "shell_exec"
    "exec("
)

for file in $(git diff --cached --name-only --diff-filter=ACM | grep '\.php$'); do
    for pattern in "${malware_patterns[@]}"; do
        if grep -q "$pattern" "$file"; then
            echo "ERROR: Suspicious pattern '$pattern' found in $file"
            echo "Commit aborted for security."
            exit 1
        fi
    done
done

echo "Security check passed."
exit 0
EOF

chmod +x .git/hooks/pre-commit
```

---

### 5. **Directory Permissions (Critical)**

```bash
cd /var/www/invoiceinventory

# Set ownership
sudo chown -R www-data:www-data .

# Base permissions
sudo chmod -R 755 .

# Writable directories
sudo chmod -R 775 storage bootstrap/cache

# Protect sensitive files
sudo chmod 644 .env
sudo chmod 644 config/*.php

# Prevent execution in uploads
sudo tee public/storage/.htaccess > /dev/null << 'EOF'
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>
EOF
```

---

### 6. **Apache Configuration**

Edit `/etc/apache2/sites-available/etracking-ssl.conf`:

```apache
<VirtualHost *:443>
    ServerName etracking-gambia.gm
    DocumentRoot /var/www/invoiceinventory/public
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    
    # Disable directory listing
    <Directory /var/www/invoiceinventory/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Block access to sensitive files
    <FilesMatch "^\.env|^\.git|^composer\.(json|lock)|^package\.json|^README\.md">
        Require all denied
    </FilesMatch>
    
    # Disable PHP execution in uploads
    <Directory /var/www/invoiceinventory/public/storage>
        <FilesMatch "\.php$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
</VirtualHost>
```

Apply changes:
```bash
sudo a2enmod headers
sudo systemctl restart apache2
```

---

### 7. **Database Security**

```sql
-- Remove root remote access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Remove test database
DROP DATABASE IF EXISTS test;

-- Strong password for app user
ALTER USER 'etracking_user'@'localhost' IDENTIFIED BY 'NewStr0ng!Password@2026';

-- Minimal privileges
REVOKE ALL PRIVILEGES ON *.* FROM 'etracking_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON etracking_inventory.* TO 'etracking_user'@'localhost';

FLUSH PRIVILEGES;
```

Update `.env` with new password.

---

### 8. **Regular Maintenance**

#### Daily:
```bash
# Check logs
sudo tail -100 /var/log/apache2/error.log
sudo tail -100 storage/logs/laravel.log

# Run security scan
php security_scan.php
```

#### Weekly:
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Check fail2ban status
sudo fail2ban-client status

# Review banned IPs
sudo fail2ban-client status apache-auth
```

#### Monthly:
```bash
# Full security audit
sudo rkhunter --check
sudo aide --check
```

---

### 9. **Monitoring & Alerts**

#### Install Log Monitoring:
```bash
sudo apt install logwatch -y

# Daily email reports
sudo logwatch --detail high --mailto your@email.com --service all
```

#### Monitor File Changes:
```bash
# Create monitoring script
sudo tee /usr/local/bin/monitor_files.sh > /dev/null << 'EOF'
#!/bin/bash
inotifywait -m -r -e modify,create,delete /var/www/invoiceinventory/public --exclude '(storage|node_modules)' |
while read path action file; do
    echo "$(date): $action on $path$file" | mail -s "File Change Alert" your@email.com
done
EOF

sudo chmod +x /usr/local/bin/monitor_files.sh

# Install inotify tools
sudo apt install inotify-tools -y
```

---

### 10. **Access Control**

#### SSH Hardening:
Edit `/etc/ssh/sshd_config`:
```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 2222  # Change default port
```

```bash
sudo systemctl restart sshd
```

#### Limit Admin Access:
In Laravel, create IP whitelist for admin panel:

```php
// app/Http/Middleware/AdminIpWhitelist.php
public function handle($request, Closure $next)
{
    $allowedIps = ['YOUR_OFFICE_IP', '203.0.113.0']; // Add your IPs
    
    if (!in_array($request->ip(), $allowedIps)) {
        Log::warning('Unauthorized admin access attempt', [
            'ip' => $request->ip(),
        ]);
        abort(403);
    }
    
    return $next($request);
}
```

---

## ✅ QUICK CHECKLIST

- [ ] Install fail2ban
- [ ] Disable dangerous PHP functions
- [ ] Set up UFW firewall
- [ ] Install ModSecurity WAF
- [ ] Set up AIDE file monitoring
- [ ] Configure correct file permissions
- [ ] Enable SecurityMiddleware in Laravel
- [ ] Create security_scan.php cron job
- [ ] Add Apache security headers
- [ ] Harden SSH configuration
- [ ] Change all passwords
- [ ] Set up log monitoring
- [ ] Create backup strategy
- [ ] Document all changes

---

## 🚨 EMERGENCY RESPONSE PLAN

If compromised again:

1. **Immediate Actions:**
   ```bash
   # Take site offline
   sudo a2dissite etracking-ssl
   sudo systemctl reload apache2
   
   # Kill all PHP processes
   sudo killall php-fpm8.3
   
   # Backup current state for forensics
   sudo tar -czf /root/breach_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/invoiceinventory
   ```

2. **Investigation:**
   ```bash
   # Check access logs for intrusion
   sudo grep -E "POST|wp-|eval|base64" /var/log/apache2/access.log | tail -100
   
   # Find recently modified files
   sudo find /var/www/invoiceinventory -type f -mtime -1 -ls
   
   # Check for unauthorized users
   sudo awk -F: '$3 >= 1000 {print $1}' /etc/passwd
   ```

3. **Clean & Restore:**
   ```bash
   cd /var/www/invoiceinventory
   sudo git reset --hard HEAD
   sudo git clean -fd
   # Restore .env from backup
   # Fix permissions
   # Restart services
   ```

4. **Report:**
   - Document timeline
   - Save attacker IPs
   - File incident report
   - Consider professional security audit

---

## 📊 Success Indicators

After implementing these measures:
- ✅ Security scans show 0 threats
- ✅ Fail2ban actively blocking attacks
- ✅ No suspicious log entries
- ✅ AIDE reports no unexpected changes
- ✅ All tests passing with middleware enabled

---

## 📞 Need Help?

- Monitor logs: `storage/logs/security.log`
- Test security: `php security_scan.php`
- Check fail2ban: `sudo fail2ban-client status`
- Review AIDE: `sudo aide --check`
