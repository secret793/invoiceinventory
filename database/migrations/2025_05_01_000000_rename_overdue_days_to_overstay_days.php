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
        // Check if overdue_days exists in device_retrievals
        if (Schema::hasColumn('device_retrievals', 'overdue_days')) {
            Schema::table('device_retrievals', function (Blueprint $table) {
                $table->renameColumn('overdue_days', 'overstay_days');
            });
        } else if (!Schema::hasColumn('device_retrievals', 'overstay_days')) {
            // If neither column exists, create overstay_days
            Schema::table('device_retrievals', function (Blueprint $table) {
                $table->integer('overstay_days')->default(0);
            });
        }

        // Check if overdue_days exists in monitorings
        if (Schema::hasColumn('monitorings', 'overdue_days')) {
            Schema::table('monitorings', function (Blueprint $table) {
                $table->renameColumn('overdue_days', 'overstay_days');
            });
        } else if (!Schema::hasColumn('monitorings', 'overstay_days')) {
            // If neither column exists, create overstay_days
            Schema::table('monitorings', function (Blueprint $table) {
                $table->integer('overstay_days')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if overstay_days exists in device_retrievals
        if (Schema::hasColumn('device_retrievals', 'overstay_days')) {
            Schema::table('device_retrievals', function (Blueprint $table) {
                $table->renameColumn('overstay_days', 'overdue_days');
            });
        }

        // Check if overstay_days exists in monitorings
        if (Schema::hasColumn('monitorings', 'overstay_days')) {
            Schema::table('monitorings', function (Blueprint $table) {
                $table->renameColumn('overstay_days', 'overdue_days');
            });
        }
    }
};

