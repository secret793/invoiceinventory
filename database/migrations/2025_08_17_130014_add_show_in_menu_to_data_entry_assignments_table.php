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
        Schema::table('data_entry_assignments', function (Blueprint $table) {
            $table->boolean('show_in_menu')->default(true)->after('status');
            $table->index(['allocation_point_id', 'show_in_menu'], 'idx_allocation_point_menu');
        });

        // Set show_in_menu to true for existing records, but only one per allocation point
        DB::statement("
            UPDATE data_entry_assignments dea1
            SET show_in_menu = CASE
                WHEN dea1.id = (
                    SELECT MIN(dea2.id)
                    FROM data_entry_assignments dea2
                    WHERE dea2.allocation_point_id = dea1.allocation_point_id
                    ORDER BY
                        CASE WHEN dea2.status != 'RETURNED' THEN 0 ELSE 1 END,
                        dea2.created_at DESC
                    LIMIT 1
                ) THEN 1
                ELSE 0
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_entry_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_allocation_point_menu');
            $table->dropColumn('show_in_menu');
        });
    }
};
