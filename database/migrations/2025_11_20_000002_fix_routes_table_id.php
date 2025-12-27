<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the id column to be auto_increment
        DB::statement('ALTER TABLE routes MODIFY COLUMN id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY');
    }

    public function down(): void
    {
        // Rollback - modify id to not auto increment
        DB::statement('ALTER TABLE routes MODIFY COLUMN id BIGINT UNSIGNED');
    }
};
