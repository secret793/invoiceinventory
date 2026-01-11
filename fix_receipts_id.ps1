#!/usr/bin/env pwsh
# Fix Receipts Table ID - Auto Increment Issue
# Usage: .\fix_receipts_id.ps1

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "FIX RECEIPTS TABLE ID - AUTO INCREMENT" -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host ""

# Method 1: Using Laravel Tinker
Write-Host "Running fix using Laravel Tinker..." -ForegroundColor Yellow
Write-Host ""

$tinkerCommand = @"
echo '=== Checking current id column ===';
`$columns = DB::select('SHOW COLUMNS FROM receipts WHERE Field = ''id''');
print_r(`$columns);

echo '\n=== Applying fix ===';
try {
    DB::statement('ALTER TABLE ``receipts`` MODIFY COLUMN ``id`` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    echo '✅ Successfully added AUTO_INCREMENT to id column\n';
} catch (Exception `$e) {
    echo '❌ Error: ' . `$e->getMessage() . '\n';
    echo 'Trying alternative approach...\n';
    
    try {
        DB::statement('ALTER TABLE ``receipts`` DROP PRIMARY KEY');
        DB::statement('ALTER TABLE ``receipts`` MODIFY COLUMN ``id`` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE ``receipts`` ADD PRIMARY KEY (``id``)');
        echo '✅ Alternative fix applied\n';
    } catch (Exception `$e2) {
        echo '❌ Alternative failed: ' . `$e2->getMessage() . '\n';
    }
}

echo '\n=== Verifying fix ===';
`$verified = DB::select('SHOW COLUMNS FROM receipts WHERE Field = ''id''');
print_r(`$verified);

if (strpos(`$verified[0]->Extra, 'auto_increment') !== false) {
    echo '\n✅✅✅ SUCCESS! The id column now has AUTO_INCREMENT ✅✅✅\n';
} else {
    echo '\n❌ The fix did not work. Manual intervention required.\n';
}
"@

# Execute the tinker command
php artisan tinker --execute="$tinkerCommand"

Write-Host ""
Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "Script completed. Check output above for results." -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan
