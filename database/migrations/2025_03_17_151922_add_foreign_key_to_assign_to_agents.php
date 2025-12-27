<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            // Just add the foreign key without trying to drop anything
            if (!Schema::hasColumn('assign_to_agents', 'allocation_point_id')) {
                $table->unsignedBigInteger('allocation_point_id')->nullable();
            }
            
            // Add foreign key relationship if it doesn't exist
            if (!Schema::hasTable('assign_to_agents_allocation_point_id_foreign')) {
                $table->foreign('allocation_point_id')
                    ->references('id')
                    ->on('allocation_points')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (Schema::hasColumn('assign_to_agents', 'allocation_point_id')) {
                $table->dropForeign(['allocation_point_id']);
                $table->dropColumn('allocation_point_id');
            }
        });
    }
};
