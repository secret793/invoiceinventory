@echo off
echo ========================================
echo    OVERSTAY DAYS TESTING SCRIPT
echo ========================================
echo.

echo Step 1: Creating test data...
php artisan tinker --execute="require 'test_overstay_data.php';"
echo.

echo Step 2: Debugging overstay days before update...
echo --- BEFORE UPDATE ---
php artisan debug:overstay-days
echo.

echo Step 3: Running overstay calculation update...
php artisan app:update-overdue-hours
echo.

echo Step 4: Debugging overstay days after update...
echo --- AFTER UPDATE ---
php artisan debug:overstay-days
echo.

echo Step 5: Testing specific devices...
echo --- Testing TEST-OVERSTAY-001 (Expected: 3 days, D2000) ---
php artisan debug:overstay-days TEST-OVERSTAY-001
echo.

echo --- Testing TEST-OVERSTAY-002 (Expected: 5 days, D4000) ---
php artisan debug:overstay-days TEST-OVERSTAY-002
echo.

echo --- Testing TEST-OVERSTAY-003 (Expected: 0 days, D0) ---
php artisan debug:overstay-days TEST-OVERSTAY-003
echo.

echo --- Testing TEST-OVERSTAY-004 (Expected: 5 days, D4000) ---
php artisan debug:overstay-days TEST-OVERSTAY-004
echo.

echo ========================================
echo    TESTING COMPLETE
echo ========================================
pause
