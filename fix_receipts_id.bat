@echo off
REM Fix Receipts Table ID - Auto Increment Issue
REM Usage: fix_receipts_id.bat

echo =================================================================
echo FIX RECEIPTS TABLE ID - AUTO INCREMENT
echo =================================================================
echo.

echo Running fix using Laravel Artisan...
echo.

php artisan tinker --execute="echo '=== Current id column ==='; $columns = DB::select('SHOW COLUMNS FROM receipts WHERE Field = ''id'''); print_r($columns); echo '\n=== Applying fix ==='; try { DB::statement('ALTER TABLE `receipts` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'); echo '✅ Successfully added AUTO_INCREMENT\n'; } catch (Exception $e) { echo '❌ Error: ' . $e->getMessage() . '\n'; } echo '\n=== Verification ==='; $verify = DB::select('SHOW COLUMNS FROM receipts WHERE Field = ''id'''); print_r($verify);"

echo.
echo =================================================================
echo Script completed. Check output above.
echo =================================================================
pause
