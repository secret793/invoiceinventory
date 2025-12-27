<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds long route support to device_retrieval_report_2_logs table.
     * It allows storing both regular routes and long routes in the report for better
     * tracking and filtering capabilities.
     */
    public function up(): void
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            // Add long_route_id column if it doesn't exist
            if (!Schema::hasColumn('device_retrieval_report_2_logs', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable()->after('route_id');
                $table->foreign('long_route_id')
                    ->references('id')
                    ->on('long_routes')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            // Drop the foreign key and column
            if (Schema::hasColumn('device_retrieval_report_2_logs', 'long_route_id')) {
                $table->dropForeign(['long_route_id']);
                $table->dropColumn('long_route_id');
            }
        });
    }
};
