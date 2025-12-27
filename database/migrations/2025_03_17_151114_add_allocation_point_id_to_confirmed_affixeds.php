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
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            $table->unsignedBigInteger('allocation_point_id')->nullable()->after('driver_name');
            $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            $table->dropForeign(['allocation_point_id']);
            $table->dropColumn('allocation_point_id');
        });
    }
};
