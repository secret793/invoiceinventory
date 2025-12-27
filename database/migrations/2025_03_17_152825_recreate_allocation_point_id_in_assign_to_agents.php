<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, rename the existing column to temporary name to preserve data
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->renameColumn('allocation_point_id', 'allocation_point_id_old');
        });

        // Create new column with correct configuration
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->unsignedBigInteger('allocation_point_id')->nullable();
        });

        // Copy data from old column to new column
        DB::statement('UPDATE assign_to_agents SET allocation_point_id = allocation_point_id_old');

        // Drop the old column
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropColumn('allocation_point_id_old');
        });

        // Add the foreign key constraint
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->foreign('allocation_point_id')
                ->references('id')
                ->on('allocation_points')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropForeign(['allocation_point_id']);
            $table->dropColumn('allocation_point_id');
        });
    }
};
