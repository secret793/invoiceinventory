#!/bin/bash

###############################################################################
# AUTOMATED SECURITY CHECK SCRIPT
# Run this daily via cron to monitor for security threats
# Usage: sudo bash daily_security_check.sh
###############################################################################

LOG_FILE="/var/log/security_check_$(date +%Y%m%d).log"
EMAIL="your@email.com"  # Change this to your email
APP_DIR="/var/www/invoiceinventory"

echo "====================================================================" | tee -a "$LOG_FILE"
echo "Security Check Started: $(date)" | tee -a "$LOG_FILE"
echo "====================================================================" | tee -a "$LOG_FILE"

ISSUES_FOUND=0

# 1. Run PHP malware scanner
echo -e "\n[1/10] Running malware scanner..." | tee -a "$LOG_FILE"
cd "$APP_DIR" || exit
SCAN_OUTPUT=$(php security_scan.php 2>&1)
echo "$SCAN_OUTPUT" >> "$LOG_FILE"

if echo "$SCAN_OUTPUT" | grep -q "CRITICAL THREATS DETECTED"; then
    echo "❌ MALWARE DETECTED!" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo "✅ No malware detected" | tee -a "$LOG_FILE"
fi

# 2. Check for unauthorized file modifications
echo -e "\n[2/10] Checking for recent file modifications..." | tee -a "$LOG_FILE"
RECENT_FILES=$(find "$APP_DIR/public" -type f -mtime -1 -not -path "*/storage/*" -not -path "*/cache/*" 2>/dev/null)
if [ -n "$RECENT_FILES" ]; then
    echo "⚠️  Files modified in last 24 hours:" | tee -a "$LOG_FILE"
    echo "$RECENT_FILES" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo "✅ No suspicious modifications" | tee -a "$LOG_FILE"
fi

# 3. Check for WordPress files (shouldn't exist in Laravel)
echo -e "\n[3/10] Checking for WordPress files..." | tee -a "$LOG_FILE"
WP_FILES=$(find "$APP_DIR/public" -name "wp-*.php" 2>/dev/null)
if [ -n "$WP_FILES" ]; then
    echo "❌ WordPress files found (MALWARE):" | tee -a "$LOG_FILE"
    echo "$WP_FILES" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo "✅ No WordPress files" | tee -a "$LOG_FILE"
fi

# 4. Check .env file integrity
echo -e "\n[4/10] Checking .env file..." | tee -a "$LOG_FILE"
if [ -f "$APP_DIR/.env" ]; then
    if grep -q "APP_KEY=" "$APP_DIR/.env" && grep -q "DB_DATABASE=" "$APP_DIR/.env"; then
        echo "✅ .env file intact" | tee -a "$LOG_FILE"
    else
        echo "❌ .env file corrupted or incomplete" | tee -a "$LOG_FILE"
        ISSUES_FOUND=$((ISSUES_FOUND + 1))
    fi
else
    echo "❌ .env file missing!" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
fi

# 5. Check file permissions
echo -e "\n[5/10] Checking file permissions..." | tee -a "$LOG_FILE"
WRITABLE_PUBLIC=$(find "$APP_DIR/public" -type f -perm -002 2>/dev/null | head -5)
if [ -n "$WRITABLE_PUBLIC" ]; then
    echo "⚠️  World-writable files found in public/:" | tee -a "$LOG_FILE"
    echo "$WRITABLE_PUBLIC" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo "✅ Permissions correct" | tee -a "$LOG_FILE"
fi

# 6. Check for failed login attempts
echo -e "\n[6/10] Checking for failed logins..." | tee -a "$LOG_FILE"
FAILED_LOGINS=$(grep "Failed password" /var/log/auth.log 2>/dev/null | tail -10)
if [ -n "$FAILED_LOGINS" ]; then
    FAILED_COUNT=$(echo "$FAILED_LOGINS" | wc -l)
    echo "⚠️  $FAILED_COUNT failed login attempts:" | tee -a "$LOG_FILE"
    echo "$FAILED_LOGINS" | tee -a "$LOG_FILE"
else
    echo "✅ No recent failed logins" | tee -a "$LOG_FILE"
fi

# 7. Check Apache error logs for suspicious activity
echo -e "\n[7/10] Checking Apache errors..." | tee -a "$LOG_FILE"
APACHE_ERRORS=$(grep -E "wp-|eval|base64_decode|shell" /var/log/apache2/error.log 2>/dev/null | tail -5)
if [ -n "$APACHE_ERRORS" ]; then
    echo "⚠️  Suspicious Apache errors:" | tee -a "$LOG_FILE"
    echo "$APACHE_ERRORS" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
else
    echo "✅ No suspicious Apache errors" | tee -a "$LOG_FILE"
fi

# 8. Check fail2ban status
echo -e "\n[8/10] Checking fail2ban status..." | tee -a "$LOG_FILE"
if systemctl is-active --quiet fail2ban; then
    BANNED_IPS=$(fail2ban-client status 2>/dev/null | grep "Currently banned" | awk '{print $4}')
    echo "✅ Fail2ban active - $BANNED_IPS IPs banned" | tee -a "$LOG_FILE"
else
    echo "❌ Fail2ban not running!" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
fi

# 9. Check disk space
echo -e "\n[9/10] Checking disk space..." | tee -a "$LOG_FILE"
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 90 ]; then
    echo "❌ Disk usage critical: ${DISK_USAGE}%" | tee -a "$LOG_FILE"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
elif [ "$DISK_USAGE" -gt 75 ]; then
    echo "⚠️  Disk usage high: ${DISK_USAGE}%" | tee -a "$LOG_FILE"
else
    echo "✅ Disk usage normal: ${DISK_USAGE}%" | tee -a "$LOG_FILE"
fi

# 10. Check Laravel logs for errors
echo -e "\n[10/10] Checking Laravel logs..." | tee -a "$LOG_FILE"
LARAVEL_ERRORS=$(tail -50 "$APP_DIR/storage/logs/laravel.log" 2>/dev/null | grep -i "error\|exception" | tail -5)
if [ -n "$LARAVEL_ERRORS" ]; then
    echo "⚠️  Recent Laravel errors:" | tee -a "$LOG_FILE"
    echo "$LARAVEL_ERRORS" | tee -a "$LOG_FILE"
else
    echo "✅ No recent Laravel errors" | tee -a "$LOG_FILE"
fi

# Summary
echo -e "\n====================================================================" | tee -a "$LOG_FILE"
echo "Security Check Completed: $(date)" | tee -a "$LOG_FILE"
echo "Issues Found: $ISSUES_FOUND" | tee -a "$LOG_FILE"
echo "====================================================================" | tee -a "$LOG_FILE"

# Send email if issues found
if [ "$ISSUES_FOUND" -gt 0 ]; then
    echo "🚨 SENDING ALERT EMAIL..." | tee -a "$LOG_FILE"
    
    # Send via mail command (requires mailutils)
    if command -v mail &> /dev/null; then
        cat "$LOG_FILE" | mail -s "⚠️ SECURITY ALERT: $ISSUES_FOUND issues found on $(hostname)" "$EMAIL"
        echo "Alert email sent to $EMAIL" | tee -a "$LOG_FILE"
    else
        echo "⚠️  Mail command not available. Install: sudo apt install mailutils" | tee -a "$LOG_FILE"
    fi
else
    echo "✅ All checks passed - No action needed" | tee -a "$LOG_FILE"
fi

exit $ISSUES_FOUND
