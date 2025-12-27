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
        // Fix the id column to have AUTO_INCREMENT
        DB::statement('ALTER TABLE `confirmed_affix_logs` MODIFY `id` BIGINT UNSIGNED AUTO_INCREMENT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to non-autoincrement
        DB::statement('ALTER TABLE `confirmed_affix_logs` MODIFY `id` BIGINT UNSIGNED');
    }
};
