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
        // Check if migrations table exists
        if (Schema::hasTable('migrations')) {
            // Check if id column exists and is not auto-increment
            $columns = DB::select("SHOW COLUMNS FROM migrations WHERE Field = 'id'");
            
            if (empty($columns) || strpos($columns[0]->Extra, 'auto_increment') === false) {
                // Drop and recreate the migrations table with proper auto-increment
                Schema::dropIfExists('migrations_backup');
                
                // Create backup of existing migrations
                DB::statement('CREATE TABLE migrations_backup AS SELECT * FROM migrations');
                
                // Drop and recreate migrations table
                Schema::dropIfExists('migrations');
                
                Schema::create('migrations', function (Blueprint $table) {
                    $table->id();
                    $table->string('migration');
                    $table->integer('batch');
                });
                
                // Restore data from backup
                DB::statement('INSERT INTO migrations (migration, batch) SELECT migration, batch FROM migrations_backup');
                
                // Add our recent migration
                DB::table('migrations')->insert([
                    'migration' => '2025_05_01_000000_rename_overdue_days_to_overstay_days',
                    'batch' => DB::table('migrations')->max('batch') + 1
                ]);
                
                // Drop backup table
                Schema::dropIfExists('migrations_backup');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed
    }
};