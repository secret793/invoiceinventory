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
        Schema::table('dispatch_logs', function (Blueprint $table) {
            // Add assign_to_agent_id if it doesn't exist
            if (!Schema::hasColumn('dispatch_logs', 'assign_to_agent_id')) {
                $table->unsignedBigInteger('assign_to_agent_id')->nullable()->after('device_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_logs', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_logs', 'assign_to_agent_id')) {
                $table->dropColumn('assign_to_agent_id');
            }
        });
    }
};
