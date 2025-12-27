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
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            // Add route_id column with foreign key constraint
            if (!Schema::hasColumn('device_retrieval_report_2_logs', 'route_id')) {
                $table->unsignedBigInteger('route_id')->nullable();
                
                // Add foreign key constraint
                $table->foreign('route_id')
                      ->references('id')
                      ->on('routes')
                      ->onDelete('set null');
            }

            // Add route_name column if it doesn't exist
            if (!Schema::hasColumn('device_retrieval_report_2_logs', 'route_name')) {
                $table->string('route_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['route_id']);
            
            // Then drop columns
            $table->dropColumn(['route_id', 'route_name']);
        });
    }
};