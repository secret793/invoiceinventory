<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            if (!Schema::hasColumn('confirmed_affixeds', 'allocation_point_id')) {
                $table->foreignId('allocation_point_id')->nullable()->constrained('allocation_points')->onDelete('set null');
            }
        });

        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('assign_to_agents', 'allocation_point_id')) {
                $table->foreignId('allocation_point_id')->nullable()->constrained('allocation_points')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            if (Schema::hasColumn('confirmed_affixeds', 'allocation_point_id')) {
                $table->dropForeign(['allocation_point_id']);
                $table->dropColumn('allocation_point_id');
            }
        });

        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (Schema::hasColumn('assign_to_agents', 'allocation_point_id')) {
                $table->dropForeign(['allocation_point_id']);
                $table->dropColumn('allocation_point_id');
            }
        });
    }
};