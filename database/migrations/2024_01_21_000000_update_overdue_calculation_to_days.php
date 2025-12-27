<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First add overdue_days column to both tables
        Schema::table('monitorings', function (Blueprint $table) {
            if (!Schema::hasColumn('monitorings', 'overdue_days')) {
                $table->integer('overdue_days')->default(0);
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'overdue_days')) {
                $table->integer('overdue_days')->default(0);
            }
        });

        // Now convert existing overdue_hours to days
        DB::statement('
            UPDATE monitorings 
            SET overdue_days = CEILING(IFNULL(overdue_hours, 0) / 24) 
            WHERE overdue_hours > 0
        ');

        DB::statement('
            UPDATE device_retrievals 
            SET overdue_days = CEILING(IFNULL(overdue_hours, 0) / 24) 
            WHERE overdue_hours > 0
        ');

        // Finally drop overdue_hours column
        Schema::table('monitorings', function (Blueprint $table) {
            if (Schema::hasColumn('monitorings', 'overdue_hours')) {
                $table->dropColumn('overdue_hours');
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (Schema::hasColumn('device_retrievals', 'overdue_hours')) {
                $table->dropColumn('overdue_hours');
            }
        });
    }

    public function down()
    {
        // Add back overdue_hours column
        Schema::table('monitorings', function (Blueprint $table) {
            if (!Schema::hasColumn('monitorings', 'overdue_hours')) {
                $table->integer('overdue_hours')->default(0);
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'overdue_hours')) {
                $table->integer('overdue_hours')->default(0);
            }
        });

        // Convert days back to hours
        DB::statement('
            UPDATE monitorings 
            SET overdue_hours = IFNULL(overdue_days, 0) * 24 
            WHERE overdue_days > 0
        ');

        DB::statement('
            UPDATE device_retrievals 
            SET overdue_hours = IFNULL(overdue_days, 0) * 24 
            WHERE overdue_days > 0
        ');

        // Drop overdue_days column
        Schema::table('monitorings', function (Blueprint $table) {
            if (Schema::hasColumn('monitorings', 'overdue_days')) {
                $table->dropColumn('overdue_days');
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (Schema::hasColumn('device_retrievals', 'overdue_days')) {
                $table->dropColumn('overdue_days');
            }
        });
    }
}; 