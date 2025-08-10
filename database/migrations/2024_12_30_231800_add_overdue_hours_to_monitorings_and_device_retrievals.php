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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn('overdue_hours');
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn('overdue_hours');
        });
    }
};
