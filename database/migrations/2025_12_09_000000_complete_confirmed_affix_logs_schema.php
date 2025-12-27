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
        Schema::table('confirmed_affix_logs', function (Blueprint $table) {
            // Add all missing columns if they don't exist
            if (!Schema::hasColumn('confirmed_affix_logs', 'device_id')) {
                $table->unsignedBigInteger('device_id')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'boe')) {
                $table->string('boe')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'sad_number')) {
                $table->string('sad_number')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'vehicle_number')) {
                $table->string('vehicle_number')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'regime')) {
                $table->string('regime')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'destination')) {
                $table->string('destination')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'destination_id')) {
                $table->unsignedBigInteger('destination_id')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'route_id')) {
                $table->unsignedBigInteger('route_id')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'manifest_date')) {
                $table->date('manifest_date')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'agency')) {
                $table->string('agency')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'agent_contact')) {
                $table->string('agent_contact')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'truck_number')) {
                $table->string('truck_number')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'driver_name')) {
                $table->string('driver_name')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'affixing_date')) {
                $table->date('affixing_date')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'status')) {
                $table->string('status')->default('PENDING');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'allocation_point_id')) {
                $table->unsignedBigInteger('allocation_point_id')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'affixed_by')) {
                $table->unsignedBigInteger('affixed_by')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confirmed_affix_logs', function (Blueprint $table) {
            // Remove columns in reverse order
            $columns = [
                'device_id', 'boe', 'sad_number', 'vehicle_number', 'regime',
                'destination', 'destination_id', 'route_id', 'long_route_id',
                'manifest_date', 'agency', 'agent_contact', 'truck_number',
                'driver_name', 'affixing_date', 'status', 'allocation_point_id', 'affixed_by'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('confirmed_affix_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
