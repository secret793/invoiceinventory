<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix allocation_points table to have BIGINT AUTO_INCREMENT
        DB::statement('ALTER TABLE `allocation_points` MODIFY `id` BIGINT UNSIGNED AUTO_INCREMENT');
        
        // Fix destinations table to have INT AUTO_INCREMENT
        DB::statement('ALTER TABLE `destinations` MODIFY `id` INT UNSIGNED AUTO_INCREMENT');
        
        // Fix receipts table column types to match
        DB::statement('ALTER TABLE `receipts` MODIFY `allocation_point_id` BIGINT UNSIGNED');
        DB::statement('ALTER TABLE `receipts` MODIFY `destination_id` INT UNSIGNED');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `receipts` MODIFY `allocation_point_id` INT UNSIGNED');
        DB::statement('ALTER TABLE `receipts` MODIFY `destination_id` INT UNSIGNED');
    }
};
