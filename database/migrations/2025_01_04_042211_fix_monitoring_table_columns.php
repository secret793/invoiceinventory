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
        Schema::table('monitoring', function (Blueprint $table) {
            // Rename boe_number to boe if it exists
            if (Schema::hasColumn('monitoring', 'boe_number')) {
                $table->renameColumn('boe_number', 'boe');
            }
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('monitoring', 'boe')) {
                $table->string('boe')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'sad_number')) {
                $table->string('sad_number')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'vehicle_number')) {
                $table->string('vehicle_number')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'regime')) {
                $table->string('regime')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'destination')) {
                $table->string('destination')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'agency')) {
                $table->string('agency')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'agent_contact')) {
                $table->string('agent_contact')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'truck_number')) {
                $table->string('truck_number')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'driver_name')) {
                $table->string('driver_name')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'affixing_date')) {
                $table->timestamp('affixing_date')->nullable();
            }
            if (!Schema::hasColumn('monitoring', 'status')) {
                $table->string('status')->default('PENDING');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring', function (Blueprint $table) {
            // Only drop columns that we added
            $columns = [
                'boe', 'sad_number', 'vehicle_number', 'regime', 'destination',
                'agency', 'agent_contact', 'truck_number', 'driver_name',
                'affixing_date', 'status'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('monitoring', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
