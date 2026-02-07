# 🚀 Deployment Guide: Push to GitHub & Update Server

## Part 1: Push Changes to GitHub

### Step 1: Check Current Status
```bash
cd c:\laragon2\www\inventory\invoiceinventory
git status
```

### Step 2: Add Files to Git
```bash
# Add the diagnostic scripts
git add public/diagnose_server.php
git add diagnose_server.sh

# Or add all changes
git add .
```

### Step 3: Commit Changes
```bash
git commit -m "Add server diagnostic scripts for Apache routing troubleshooting"
```

### Step 4: Push to GitHub
```bash
# Push to main branch (or master, depending on your setup)
git push origin main

# If you get an error, try:
git push origin master
```

### Alternative: If Repository Not Set Up Yet
```bash
# Initialize git (if not already done)
git init

# Add remote repository
git remote add origin https://github.com/YOUR-USERNAME/YOUR-REPO-NAME.git

# Add all files
git add .

# Commit
git commit -m "Initial commit with diagnostic scripts"

# Push
git push -u origin main
```

---

## Part 2: Connect to Server via SSH

### Option A: Using PowerShell (Windows)
```powershell
# Connect to server
ssh administrator@38.247.134.227

# When prompted, enter password: Sha******ack

# Switch to root user
sudo -i
```

### Option B: Using PuTTY (Windows GUI)
1. Download PuTTY: https://www.putty.org/
2. Open PuTTY
3. Enter:
   - **Host Name**: `38.247.134.227`
   - **Port**: `22`
   - **Connection Type**: SSH
4. Click "Open"
5. Login as: `administrator`
6. Password: `Sha******ack`
7. Run: `sudo -i` (to become root)

---

## Part 3: Update Server from GitHub

### Step 1: Locate Your Project on Server
```bash
# Find the project directory
cd /var/www/html
ls -la

# Or search for it
find /var/www -name "invoiceinventory" -type d 2>/dev/null
find /home -name "invoiceinventory" -type d 2>/dev/null

# Once found, go to project directory (example path):
cd /var/www/html/invoiceinventory
```

### Step 2: Check Git Status
```bash
# See current branch and changes
git status

# See current remote URL
git remote -v
```

### Step 3: Pull Latest Changes from GitHub
```bash
# Fetch and pull latest changes
git fetch origin
git pull origin main

# Or if using master branch:
git pull origin master

# If you get merge conflicts:
git stash          # Temporarily save local changes
git pull origin main
git stash pop      # Reapply local changes
```

### Step 4: Alternative - Clone Fresh (If Not Set Up Yet)
```bash
# Navigate to web root
cd /var/www/html

# Clone repository (replace with your actual GitHub URL)
git clone https://github.com/YOUR-USERNAME/YOUR-REPO-NAME.git invoiceinventory

# Enter directory
cd invoiceinventory
```

---

## Part 4: Run Diagnostic Scripts

### Method 1: Run Shell Script (Recommended)
```bash
# Make sure you're in the project directory
cd /var/www/html/invoiceinventory  # or wherever your project is

# Make script executable
chmod +x diagnose_server.sh

# Run the diagnostic script as root
sudo bash diagnose_server.sh

# Save output to file for review
sudo bash diagnose_server.sh > diagnostic_report.txt 2>&1

# View the report
cat diagnostic_report.txt
```

### Method 2: Access PHP Script via Browser
```bash
# First, make sure the PHP file has correct permissions
chmod 644 public/diagnose_server.php

# Then open in browser:
# https://etracking-gambia.gm/diagnose_server.php
```

---

## Part 5: Fix Common Issues Based on Diagnostics

### Fix 1: Enable mod_rewrite
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Fix 2: Set Correct DocumentRoot
```bash
# Find your virtual host file
ls -la /etc/apache2/sites-enabled/

# Edit the config (replace filename with actual)
sudo nano /etc/apache2/sites-enabled/000-default.conf
# or
sudo nano /etc/apache2/sites-enabled/etracking-gambia-le-ssl.conf

# Change DocumentRoot to point to public folder:
# DocumentRoot /var/www/html/invoiceinventory/public

# Save: Ctrl+O, Enter, Ctrl+X
```

### Fix 3: Set AllowOverride All
```bash
# Edit the same virtual host file
sudo nano /etc/apache2/sites-enabled/000-default.conf

# Add or modify the Directory block:
# <Directory /var/www/html/invoiceinventory/public>
#     AllowOverride All
#     Require all granted
# </Directory>

# Test configuration
sudo apache2ctl configtest

# If OK, restart Apache
sudo systemctl restart apache2
```

### Fix 4: Set Correct File Permissions
```bash
# Navigate to project
cd /var/www/html/invoiceinventory

# Set owner (replace www-data if your Apache user is different)
sudo chown -R www-data:www-data .

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Make storage and cache writable
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Fix 5: Clear Laravel Cache
```bash
cd /var/www/html/invoiceinventory

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimized files
php artisan config:cache
php artisan route:cache
```

---

## Part 6: Test the Fix

### Test 1: Check if Laravel is Accessible
```bash
# Try to access the site
curl -I https://etracking-gambia.gm

# You should see HTTP/1.1 200 OK or a redirect, not 404
```

### Test 2: Check Apache Error Logs
```bash
# View recent errors
sudo tail -50 /var/log/apache2/error.log

# Monitor errors in real-time
sudo tail -f /var/log/apache2/error.log
# (Ctrl+C to stop)
```

### Test 3: Check Virtual Host Configuration
```bash
# Show Apache virtual hosts
sudo apache2ctl -S
```

---

## 📋 Complete Command Sequence (Copy-Paste Ready)

### On Your Local Machine (Windows PowerShell):
```powershell
# 1. Push to GitHub
cd c:\laragon2\www\inventory\invoiceinventory
git add .
git commit -m "Add diagnostic scripts and fixes"
git push origin main
```

### On Your Server (SSH):
```bash
# 2. Connect to server
ssh administrator@38.247.134.227
# Enter password when prompted

# 3. Switch to root
sudo -i

# 4. Navigate to project (adjust path as needed)
cd /var/www/html/invoiceinventory

# 5. Pull latest changes
git pull origin main

# 6. Run diagnostics
chmod +x diagnose_server.sh
bash diagnose_server.sh

# 7. Enable mod_rewrite (if needed)
a2enmod rewrite

# 8. Edit virtual host (find the correct file first)
ls -la /etc/apache2/sites-enabled/
# Then edit the SSL config file (likely has -le-ssl in name)
nano /etc/apache2/sites-enabled/etracking-gambia-le-ssl.conf

# 9. Test and restart Apache
apache2ctl configtest
systemctl restart apache2

# 10. Set permissions
cd /var/www/html/invoiceinventory
chown -R www-data:www-data .
chmod -R 775 storage bootstrap/cache

# 11. Clear Laravel cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# 12. Test
curl -I https://etracking-gambia.gm
```

---

## 🆘 Troubleshooting

### Can't Connect via SSH?
```powershell
# Test connection
Test-NetConnection -ComputerName 38.247.134.227 -Port 22

# If blocked, check firewall or use VPN
```

### Permission Denied on Git Pull?
```bash
# Check if .git directory is owned by current user
ls -la .git

# Fix ownership
sudo chown -R administrator:administrator .git
```

### Apache Won't Restart?
```bash
# Check for syntax errors
sudo apache2ctl configtest

# Check detailed status
sudo systemctl status apache2

# View full error log
sudo journalctl -u apache2 -n 50
```

---

## 📞 What to Tell Your Hosting Provider

If diagnostic shows issues, send them this:

> "My Laravel application shows 404 errors. Diagnostics show:
> 1. DocumentRoot needs to point to: `/var/www/html/invoiceinventory/public`
> 2. AllowOverride must be set to: `All`
> 3. mod_rewrite must be enabled
> 
> Can you please update the virtual host configuration for etracking-gambia.gm?"

---

## ✅ Success Indicators

You'll know it's fixed when:
- ✅ `https://etracking-gambia.gm` shows your Laravel app (not Apache 404)
- ✅ `diagnose_server.php` shows all green checkmarks
- ✅ Laravel routes work correctly

---

**Need help?** Run the diagnostic script and share the output!
