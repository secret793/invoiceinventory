<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks to allow modifying columns
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // First, add PRIMARY KEY to tables that don't have it
            $tablesNeedingPK = [
                'other_items',
                'permission_stored',
                'permission_storeds',
                'personal_access_tokens',
                'regimes',
                'reports',
                'roles',
                'stores',
                'transfers',
                'user_allocation_points',
                'vehicles'
            ];
            
            foreach ($tablesNeedingPK as $table) {
                // Check if there's already a primary key, if not add it
                $hasPK = DB::select("SELECT 1 FROM information_schema.KEY_COLUMN_USAGE 
                                    WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id' AND CONSTRAINT_NAME = 'PRIMARY'
                                    AND TABLE_SCHEMA = DATABASE()", [$table]);
                
                if (empty($hasPK)) {
                    DB::statement("ALTER TABLE $table ADD PRIMARY KEY (id)");
                }
            }
            
            // Now add AUTO_INCREMENT to all tables
            DB::statement("ALTER TABLE data_entry_assignments MODIFY id int AUTO_INCREMENT");
            DB::statement("ALTER TABLE destination_user MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE destinations MODIFY id int AUTO_INCREMENT");
            DB::statement("ALTER TABLE device_retrieval_logs MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE device_retrieval_report_2_logs MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE devices MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE dispatches MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE distribution_point_user MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE distribution_points MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE distribution_status_history MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE failed_jobs MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE media MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE other_items MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE permission_stored MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE permission_storeds MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE personal_access_tokens MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE regimes MODIFY id int AUTO_INCREMENT");
            DB::statement("ALTER TABLE reports MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE roles MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE stores MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE transfers MODIFY id int AUTO_INCREMENT");
            DB::statement("ALTER TABLE user_allocation_points MODIFY id bigint unsigned AUTO_INCREMENT");
            DB::statement("ALTER TABLE vehicles MODIFY id bigint unsigned AUTO_INCREMENT");
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - these changes are structural
        // If needed, recreate the database from backup
    }
};
