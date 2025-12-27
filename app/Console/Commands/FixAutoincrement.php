<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAutoincrement extends Command
{
    protected $signature = 'fix:autoincrement';
    protected $description = 'Fix AUTO_INCREMENT issues on tables';

    public function handle()
    {
        $this->line("\n═══════════════════════════════════════════════════════════════");
        $this->line("    FIXING AUTO-INCREMENT ISSUES");
        $this->line("═══════════════════════════════════════════════════════════════\n");

        try {
            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 1. Fix users table
            $this->line('1. Fixing users table...');
            DB::statement('ALTER TABLE `users` MODIFY `id` BIGINT UNSIGNED AUTO_INCREMENT');
            $this->line("   ✓ users.id is now AUTO_INCREMENT\n");

            // 2. Fix allocation_points table
            $this->line('2. Fixing allocation_points table...');
            DB::statement('ALTER TABLE `allocation_points` MODIFY `id` BIGINT UNSIGNED AUTO_INCREMENT');
            $this->line("   ✓ allocation_points.id is now AUTO_INCREMENT\n");

            // 3. Fix destinations table
            $this->line('3. Fixing destinations table...');
            DB::statement('ALTER TABLE `destinations` MODIFY `id` INT UNSIGNED AUTO_INCREMENT');
            $this->line("   ✓ destinations.id is now AUTO_INCREMENT\n");

            // 4. Fix receipts table columns to match FK targets
            $this->line('4. Fixing receipts table columns...');
            DB::statement('ALTER TABLE `receipts` MODIFY `allocation_point_id` BIGINT UNSIGNED');
            DB::statement('ALTER TABLE `receipts` MODIFY `destination_id` INT UNSIGNED');
            $this->line("   ✓ receipts columns now match FK types\n");

            // 5. Fix receipts table
            $this->line('5. Fixing receipts table...');
            DB::statement('ALTER TABLE `receipts` MODIFY `id` BIGINT UNSIGNED AUTO_INCREMENT');
            $this->line("   ✓ receipts.id is now AUTO_INCREMENT\n");

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // 6. Verify changes
            $this->line('6. Verifying changes...');
            $results = DB::select("
                SELECT TABLE_NAME, COLUMN_NAME, EXTRA 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME IN ('users', 'allocation_points', 'destinations', 'receipts')
                AND COLUMN_NAME = 'id'
                ORDER BY TABLE_NAME
            ");

            foreach ($results as $row) {
                $this->line("   " . str_pad($row->TABLE_NAME, 25) . " - " . $row->EXTRA);
            }

            $this->line("\n═══════════════════════════════════════════════════════════════");
            $this->line("✓ AUTO-INCREMENT FIXES COMPLETED");
            $this->line("═══════════════════════════════════════════════════════════════\n");

        } catch (Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error("\n✗ ERROR: " . $e->getMessage() . "\n");
            return 1;
        }

        return 0;
    }
}
