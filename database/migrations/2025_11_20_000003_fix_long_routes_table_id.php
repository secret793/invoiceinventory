<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        // Modify the id column to be auto_increment
        DB::statement('ALTER TABLE long_routes MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT');
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Rollback - modify id to not auto increment
        DB::statement('ALTER TABLE long_routes MODIFY COLUMN id BIGINT UNSIGNED');
    }
};
