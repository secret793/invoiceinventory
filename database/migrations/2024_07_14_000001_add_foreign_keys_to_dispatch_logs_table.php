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
        // Only proceed if the table exists
        if (Schema::hasTable('dispatch_logs')) {
            Schema::table('dispatch_logs', function (Blueprint $table) {
                // Add foreign key for devices table
                if (Schema::hasTable('devices')) {
                    $table->foreign('device_id')
                          ->references('id')
                          ->on('devices')
                          ->onDelete('cascade');
                }

                // Add foreign key for data_entry_assignments table
                if (Schema::hasTable('data_entry_assignments')) {
                    $table->foreign('data_entry_assignment_id')
                          ->references('id')
                          ->on('data_entry_assignments')
                          ->onDelete('cascade');
                }

                // Add foreign key for users table
                if (Schema::hasTable('users')) {
                    $table->foreign('dispatched_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only proceed if the table exists
        if (Schema::hasTable('dispatch_logs')) {
            Schema::table('dispatch_logs', function (Blueprint $table) {
                // Drop foreign keys if they exist
                $table->dropForeign(['device_id']);
                $table->dropForeign(['data_entry_assignment_id']);
                $table->dropForeign(['dispatched_by']);
            });
        }
    }
};
