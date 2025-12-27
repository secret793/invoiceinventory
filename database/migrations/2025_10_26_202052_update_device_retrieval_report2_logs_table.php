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
            // First modify the column type to match routes.id if it exists
            if (Schema::hasColumn('device_retrieval_report_2_logs', 'route_id')) {
                // Drop any existing foreign key constraints
                $table->dropForeign(['route_id']);
                
                // Modify the column to ensure it's the correct type
                $table->unsignedBigInteger('route_id')->nullable()->change();
            } else {
                // Add the column if it doesn't exist
                $table->unsignedBigInteger('route_id')->nullable()->after('regime');
            }

            // Add the foreign key constraint
            $table->foreign('route_id')
                  ->references('id')
                  ->on('routes')
                  ->onDelete('set null');
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
            
            // Then drop the column
            $table->dropColumn('route_id');
        });
    }
};
