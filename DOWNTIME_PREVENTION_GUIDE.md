# 🛡️ Deployment Downtime Analysis & Prevention Guide

## 🔍 Root Cause Analysis

Based on your fix (`git reset --hard`), the downtime was caused by:

### **Most Likely Causes:**

1. **Corrupted/Modified Files on Server**
   - Files were manually edited directly on the server
   - Incomplete/failed previous deployment
   - File permissions changed unexpectedly
   - Critical files (.htaccess, index.php) were altered or deleted

2. **Stale Cache Issues**
   - Route cache pointing to non-existent routes
   - Config cache with wrong settings
   - View cache corruption

3. **Git State Corruption**
   - Merge conflicts not resolved properly
   - Partial pull/checkout left files in inconsistent state

### **Why `git reset --hard` Fixed It:**
This command forcibly overwrites all local changes with the clean GitHub version, eliminating any corrupted, modified, or missing files.

---

## 🚨 Critical Prevention Strategies

### 1. **NEVER Edit Files Directly on Production Server**
```bash
# ❌ BAD - Editing on server
sudo nano /var/www/html/invoiceinventory/routes/web.php

# ✅ GOOD - Edit locally, commit, push, then deploy
# Edit on local machine → git commit → git push → deploy script
```

### 2. **Use Automated Deployment Script**
Create a safe deployment script that prevents corruption:

```bash
#!/bin/bash
# File: /var/www/html/invoiceinventory/deploy.sh

set -e  # Exit on any error

echo "🚀 Starting deployment..."

# Navigate to project
cd /var/www/html/invoiceinventory

# Put site in maintenance mode
echo "🔧 Enabling maintenance mode..."
php artisan down || true

# Backup current state
echo "💾 Creating backup..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Stash any local changes (shouldn't be any!)
git stash

# Pull latest code
echo "📥 Pulling latest code..."
git fetch origin
git reset --hard origin/master

# Restore environment file
cp .env.backup.* .env 2>/dev/null || echo "No backup needed"

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Run migrations (if any)
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear all caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set correct permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data .
chmod -R 755 storage bootstrap/cache

# Bring site back online
echo "✅ Disabling maintenance mode..."
php artisan up

echo "🎉 Deployment complete!"
```

**Usage:**
```bash
sudo bash deploy.sh
```

### 3. **Enable Zero-Downtime Deployments with Symlinks**

Use this advanced setup to avoid any downtime:

```bash
# Setup structure:
/var/www/html/
  ├── invoiceinventory (symlink → releases/20260207_143022)
  ├── releases/
  │   ├── 20260207_143022/  (current)
  │   ├── 20260206_091534/  (previous)
  │   └── 20260205_162341/  (old)
  ├── shared/
  │   ├── .env
  │   └── storage/

# Deployment script switches symlink only after new release is ready
```

**Deployment Script for Zero-Downtime:**
```bash
#!/bin/bash
# File: zero-downtime-deploy.sh

set -e

PROJECT_ROOT="/var/www/html"
RELEASES_DIR="$PROJECT_ROOT/releases"
SHARED_DIR="$PROJECT_ROOT/shared"
CURRENT_LINK="$PROJECT_ROOT/invoiceinventory"
RELEASE_NAME=$(date +%Y%m%d_%H%M%S)
NEW_RELEASE="$RELEASES_DIR/$RELEASE_NAME"

echo "🚀 Zero-downtime deployment: $RELEASE_NAME"

# Clone new release
git clone https://github.com/secret793/invoiceinventory.git "$NEW_RELEASE"
cd "$NEW_RELEASE"

# Link shared resources
ln -s "$SHARED_DIR/.env" "$NEW_RELEASE/.env"
ln -s "$SHARED_DIR/storage" "$NEW_RELEASE/storage"

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data "$NEW_RELEASE"

# Switch to new release (atomic operation)
ln -sfn "$NEW_RELEASE" "$CURRENT_LINK"

# Clean old releases (keep last 3)
cd "$RELEASES_DIR"
ls -t | tail -n +4 | xargs rm -rf

echo "✅ Deployment complete! Old app still running until symlink switched."
```

---

## 📊 Monitoring & Alerting

### 1. **Health Check Endpoint**
Create a health check to monitor your site:

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => config('app.version'),
    ]);
});
```

### 2. **Uptime Monitoring Services** (Free Options)
- **UptimeRobot** (https://uptimerobot.com) - Free, checks every 5 mins
- **Pingdom** (https://www.pingdom.com) - Free tier available
- **StatusCake** (https://www.statuscake.com) - Free plan

Setup: Monitor `https://etracking-gambia.gm/health` every 5 minutes

### 3. **Server-Side Monitoring Script**
```bash
#!/bin/bash
# File: monitor.sh - Run via cron every 5 minutes

SITE="https://etracking-gambia.gm"
ALERT_EMAIL="your-email@example.com"

STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$SITE")

if [ "$STATUS" != "200" ]; then
    echo "⚠️ ALERT: Site down! Status: $STATUS" | mail -s "Site Down Alert" "$ALERT_EMAIL"
    
    # Auto-recovery attempt
    cd /var/www/html/invoiceinventory
    php artisan up
    systemctl restart apache2
fi
```

**Add to crontab:**
```bash
crontab -e
# Add:
*/5 * * * * /var/www/html/invoiceinventory/monitor.sh
```

---

## 🔒 Security & Stability Best Practices

### 1. **File Permissions Protection**
```bash
# Set once, prevent accidental changes
sudo chown -R www-data:www-data /var/www/html/invoiceinventory
sudo chmod -R 755 /var/www/html/invoiceinventory
sudo chmod -R 775 /var/www/html/invoiceinventory/storage
sudo chmod -R 775 /var/www/html/invoiceinventory/bootstrap/cache

# Protect sensitive files
sudo chmod 600 /var/www/html/invoiceinventory/.env
```

### 2. **Git Configuration on Server**
```bash
cd /var/www/html/invoiceinventory

# Prevent accidental commits from server
git config core.fileMode false

# Ensure clean pulls
git config pull.rebase false
```

### 3. **Database Backup Before Deployment**
```bash
# Add to deploy script BEFORE migrations
mysqldump -u root -p first_crud > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 4. **Environment File Protection**
```bash
# Create immutable backup
sudo cp .env .env.master
sudo chattr +i .env.master  # Makes file immutable (cannot be deleted/modified)

# To modify: sudo chattr -i .env.master
```

---

## 📋 Deployment Checklist

### **Before Every Deployment:**
- [ ] Test locally first
- [ ] Commit and push to GitHub
- [ ] Backup database
- [ ] Backup .env file
- [ ] Check disk space: `df -h`

### **During Deployment:**
- [ ] Enable maintenance mode
- [ ] Pull latest code
- [ ] Install dependencies
- [ ] Run migrations
- [ ] Clear caches
- [ ] Set permissions
- [ ] Disable maintenance mode

### **After Deployment:**
- [ ] Test critical pages
- [ ] Check error logs
- [ ] Verify database connections
- [ ] Monitor for 15 minutes

---

## 🎯 Recommended Setup (Priority Order)

### **Immediate (Do Today):**
1. ✅ Create and use the automated `deploy.sh` script above
2. ✅ Set up UptimeRobot monitoring (free, 5 minutes)
3. ✅ Protect .env file with immutable backup
4. ✅ Document the correct deployment procedure

### **This Week:**
1. ⭐ Implement zero-downtime deployment with symlinks
2. ⭐ Set up automated database backups (daily cron)
3. ⭐ Create health check endpoint
4. ⭐ Add server monitoring script

### **This Month:**
1. 🚀 Set up staging environment for testing
2. 🚀 Implement automated tests in CI/CD
3. 🚀 Configure log monitoring (Laravel Telescope)
4. 🚀 Document rollback procedures

---

## 🔄 Quick Rollback Procedure

If deployment breaks the site:

```bash
# Option 1: Use previous Git commit
cd /var/www/html/invoiceinventory
git log --oneline  # Find previous working commit
git reset --hard <previous-commit-hash>
php artisan cache:clear
php artisan config:cache

# Option 2: Use symlink (if using zero-downtime setup)
ln -sfn /var/www/html/releases/20260206_091534 /var/www/html/invoiceinventory

# Option 3: Restore from backup
cd /var/www/html
rm -rf invoiceinventory
git clone https://github.com/secret793/invoiceinventory.git
cd invoiceinventory
cp .env.backup .env
composer install
php artisan config:cache
```

---

## 🆘 Emergency Recovery Commands

Keep these handy:

```bash
# Full recovery (what fixed your issue)
cd /var/www/html/invoiceinventory
sudo git fetch origin
sudo git reset --hard origin/master
sudo composer install --no-interaction --no-dev
sudo php artisan cache:clear
sudo php artisan config:cache
sudo chown -R www-data:www-data .
sudo systemctl restart apache2

# Just cache issues
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache

# Just permissions issues
sudo chown -R www-data:www-data /var/www/html/invoiceinventory
sudo chmod -R 755 /var/www/html/invoiceinventory
sudo chmod -R 775 storage bootstrap/cache
```

---

## 📞 Summary: Why Downtime Happened

**Root Cause:** Files on server were in an inconsistent state (corrupted, modified, or missing)

**Why It Happens:**
- Direct editing on production
- Incomplete deployments
- Permission changes
- Stale caches

**Prevention:**
1. Use automated deployment script
2. Never edit files on production directly
3. Always clear caches after deployment
4. Monitor site health
5. Keep backups

**Bottom Line:** The `git reset --hard` overwrote corrupted local files with clean GitHub versions. Using a proper deployment script will prevent this from happening again.

---

Save the `deploy.sh` script and use it for every deployment going forward! 🚀
