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
        // Fix device_retrievals table id column to have auto-increment
        if (Schema::hasTable('device_retrievals')) {
            // Check if id column exists but is not auto-increment
            $idColumn = DB::select("SHOW COLUMNS FROM device_retrievals WHERE Field = 'id'");
            if (!empty($idColumn) && strpos($idColumn[0]->Extra, 'auto_increment') === false) {
                // First check if there's a primary key
                $primaryKey = DB::select("SHOW KEYS FROM device_retrievals WHERE Key_name = 'PRIMARY'");

                Schema::table('device_retrievals', function (Blueprint $table) use ($primaryKey) {
                    // If there's a primary key, drop it first
                    if (!empty($primaryKey)) {
                        DB::statement('ALTER TABLE device_retrievals DROP PRIMARY KEY');
                    }

                    // Now modify the id column to have auto-increment
                    DB::statement('ALTER TABLE device_retrievals MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it would break the database
        // If needed, manually restore: ALTER TABLE device_retrievals MODIFY id BIGINT UNSIGNED NOT NULL;
    }
};
