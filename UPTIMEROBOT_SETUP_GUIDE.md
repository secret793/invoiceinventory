# 🔔 UptimeRobot Setup Guide - Complete Walkthrough

> **Important:** UptimeRobot is a **cloud-based service** - you don't install anything on your server. It monitors your website from the outside (like a visitor checking if your site is up).

---

## Part 1: Create UptimeRobot Account (2 minutes)

### Step 1: Sign Up (Free Forever)
1. Go to: **https://uptimerobot.com**
2. Click **"Sign Up Free"** (top right)
3. Enter your email and create a password
4. Click **"Sign Up"**
5. Check your email and **verify your account**
6. Log in to UptimeRobot dashboard

---

## Part 2: Create Your First Monitor (3 minutes)

### Step 2: Add New Monitor
1. In UptimeRobot dashboard, click **"+ Add New Monitor"** (blue button)

2. Fill in the form:
   - **Monitor Type:** `HTTP(s)`
   - **Friendly Name:** `NICK-TC-GAMBIA-INVENTORY`
   - **URL (or IP):** `https://etracking-gambia.gm`
   - **Monitoring Interval:** `5 minutes` (free tier)

3. Click **"Create Monitor"**

That's it! ✅ UptimeRobot is now checking your site every 5 minutes.

---

## Part 3: Set Up Alerts (2 minutes)

### Step 3: Configure Email Alerts (Already Enabled)
Email alerts to your registered email are **enabled by default**. You'll get notified when:
- ✅ Site goes down
- ✅ Site comes back up

### Step 4: Add Multiple Alert Contacts (Optional)
1. Go to **"My Settings"** (top right menu)
2. Click **"Alert Contacts"** tab
3. Click **"Add Alert Contact"**
4. Choose notification method:
   - **Email** (recommended)
   - **SMS** (limited free)
   - **Webhook** (for Slack/Discord/etc)
   - **Telegram**
   - **Microsoft Teams**

5. Enter details and click **"Create Alert Contact"**

---

## Part 4: Advanced Setup - Health Check Endpoint (Optional, 5 minutes)

For more detailed monitoring, create a health check endpoint on your Laravel app:

### Step 1: Add Health Check Route
```bash
# On your server
cd /var/www/html/invoiceinventory
sudo nano routes/web.php
```

Add this route:
```php
// At the top of routes/web.php, add:
Route::get('/health', function () {
    try {
        // Check database connection
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
        return response()->json([
            'status' => 'unhealthy',
            'database' => $dbStatus,
            'error' => $e->getMessage()
        ], 503);
    }

    // Check storage writable
    $storageWritable = is_writable(storage_path());

    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'database' => $dbStatus,
        'storage_writable' => $storageWritable,
        'app_env' => config('app.env'),
        'php_version' => PHP_VERSION,
    ], 200);
})->name('health');
```

### Step 2: Test the Health Check
```bash
# Test it works
curl https://etracking-gambia.gm/health

# Should return JSON like:
# {"status":"healthy","timestamp":"2026-02-07T14:30:00+00:00",...}
```

### Step 3: Update UptimeRobot Monitor
1. In UptimeRobot dashboard, click on your monitor
2. Click **"Edit"**
3. Change **URL** to: `https://etracking-gambia.gm/health`
4. Scroll down to **"Advanced Settings"**
5. Enable **"Keyword Monitoring"**
   - **Keyword Type:** `Exists`
   - **Keyword:** `healthy`
6. Click **"Save Changes"**

Now UptimeRobot will:
- ✅ Check if page loads (200 OK)
- ✅ Check if "healthy" appears in response
- ✅ Alert you if either fails

---

## Part 5: Monitor Multiple Endpoints (Recommended)

Create separate monitors for critical pages:

### Monitor #1: Home Page
- **Name:** `Main Site - Home`
- **URL:** `https://etracking-gambia.gm`
- **Interval:** 5 minutes

### Monitor #2: Health Check
- **Name:** `Health Check API`
- **URL:** `https://etracking-gambia.gm/health`
- **Interval:** 5 minutes
- **Keyword:** `healthy`

### Monitor #3: Admin Login
- **Name:** `Admin Login Page`
- **URL:** `https://etracking-gambia.gm/admin/login`
- **Interval:** 10 minutes

### Monitor #4: Database Check
- **Name:** `Dashboard Page` (requires DB)
- **URL:** `https://etracking-gambia.gm/admin`
- **Interval:** 10 minutes

---

## Part 6: View Your Monitoring Dashboard

### Dashboard Features:
- **Uptime %:** Shows 30-day uptime percentage
- **Status:** Green = Up, Red = Down
- **Response Time:** Average response time graph
- **Incidents:** Log of all downtimes
- **Public Status Page:** Share uptime with users (optional)

### Mobile Access:
- Download **UptimeRobot app** (iOS/Android)
- Get push notifications on your phone
- View status on the go

---

## Part 7: Create Public Status Page (Optional)

Let users see your site status:

1. In UptimeRobot, go to **"Public Status Pages"**
2. Click **"Add New Status Page"**
3. Configure:
   - **Name:** `NICK-TC-GAMBIA-INVENTORY Status`
   - **URL:** Choose subdomain (e.g., `nicktc-gambia.uptimerobot.com`)
   - **Monitors:** Select which monitors to show
4. Click **"Create Status Page"**
5. Share the URL with your team/users

---

## Part 8: Configure Alert Thresholds

### Prevent False Alarms:
1. Edit your monitor
2. Scroll to **"Advanced Settings"**
3. **Alert After:** `Down for 2 minutes` (recommended)
   - Prevents alerts for brief network hiccups
4. **Alert Timeout:** `5 minutes`
   - How long to wait before declaring site back up
5. Click **"Save Changes"**

---

## Part 9: Integration with Slack/Discord (Optional)

### Slack Integration:
1. In Slack, create an **Incoming Webhook**
2. Copy the webhook URL
3. In UptimeRobot:
   - Go to **My Settings → Alert Contacts**
   - Click **"Add Alert Contact"**
   - Choose **"Webhook"**
   - Paste Slack webhook URL
   - Select **"Slack"** as format
4. Edit your monitor and add this alert contact

### Discord Integration:
1. In Discord, create a webhook in your server settings
2. Copy webhook URL
3. In UptimeRobot:
   - Add as **Webhook** alert contact
   - Choose **"Discord"** format
   - Paste webhook URL

---

## 📊 What You'll Get with Free Plan

✅ **50 Monitors** (you only need 2-4)
✅ **5-minute check interval**
✅ **Email alerts** (unlimited)
✅ **2-month data retention**
✅ **Mobile app access**
✅ **Public status pages**
✅ **SSL certificate monitoring**

### Paid Plans (Optional):
- **$7/month:** 1-minute intervals, 6-month retention
- **$14/month:** SMS alerts, custom domains

---

## 🔧 Verification Checklist

After setup, verify everything works:

1. **Test Alert System:**
   ```bash
   # On your server, temporarily stop Apache
   sudo systemctl stop apache2
   
   # Wait 5 minutes - you should get an email alert
   
   # Start Apache again
   sudo systemctl start apache2
   
   # Wait 5 minutes - you should get "site is back up" email
   ```

2. **Check Dashboard:**
   - Visit UptimeRobot dashboard
   - Verify monitor shows "Up" status
   - Check response time graph

3. **Test Mobile App:**
   - Install UptimeRobot mobile app
   - Log in
   - Verify you can see your monitors

---

## 🚨 What Alerts Look Like

### Down Alert Email:
```
Subject: [Down] NICK-TC-GAMBIA-INVENTORY

NICK-TC-GAMBIA-INVENTORY is DOWN!
Reason: Connection timeout
URL: https://etracking-gambia.gm
Time: 2026-02-07 14:35:22

We'll notify you when it's back up.
```

### Up Alert Email:
```
Subject: [Up] NICK-TC-GAMBIA-INVENTORY

NICK-TC-GAMBIA-INVENTORY is back UP!
Downtime: 3 minutes 12 seconds
URL: https://etracking-gambia.gm
Time: 2026-02-07 14:38:34
```

---

## 💡 Pro Tips

1. **Set Different Intervals:**
   - Critical pages: 5 minutes
   - Less important: 10-15 minutes

2. **Use Keyword Monitoring:**
   - Monitor for "healthy" in health endpoint
   - Catches issues even if page loads but has errors

3. **Monitor SSL Certificate:**
   - UptimeRobot auto-checks SSL expiration
   - Alerts 14 days before expiry

4. **Maintenance Windows:**
   - Pause monitors during planned maintenance
   - Prevents false alerts

5. **Response Time Alerts:**
   - Get notified if site is slow (paid feature)
   - Set threshold: e.g., alert if >3 seconds

---

## 🎯 Complete Setup Summary (5 Minutes Total)

```
1. Sign up at uptimerobot.com (1 min)
2. Verify email (30 sec)
3. Add monitor for https://etracking-gambia.gm (1 min)
4. Configure alerts (30 sec)
5. Test by viewing dashboard (30 sec)
6. (Optional) Add health check endpoint (2 min)
7. (Optional) Add Slack/Discord webhook (2 min)

TOTAL: 5 minutes for basic setup
       10 minutes with health check
       15 minutes with Slack integration
```

---

## ✅ You're Done!

You now have:
- ✅ 24/7 monitoring of your website
- ✅ Instant email alerts if site goes down
- ✅ Response time tracking
- ✅ Uptime percentage reports
- ✅ Mobile app access

**Next Steps:**
1. Bookmark UptimeRobot dashboard: https://uptimerobot.com/dashboard
2. Install mobile app for push notifications
3. Share status page URL with your team

---

## 🆘 Troubleshooting

### Not Receiving Alerts?
- Check spam folder
- Verify email in **My Settings → Alert Contacts**
- Make sure monitor has alert contact assigned

### Monitor Shows "Down" But Site Works?
- Check if URL is correct (https:// vs http://)
- Verify firewall isn't blocking UptimeRobot IPs
- Check if keyword monitoring is too strict

### False Alarms?
- Increase "Alert After" to 2-3 minutes
- Check server logs during false alarm times
- Verify network stability

---

**Questions?** UptimeRobot has excellent support: support@uptimerobot.com

Happy monitoring! 🎉
