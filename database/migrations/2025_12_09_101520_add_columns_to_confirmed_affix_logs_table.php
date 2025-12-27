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
            // Check if columns don't already exist
            if (!Schema::hasColumn('confirmed_affix_logs', 'boe')) {
                $table->string('boe')->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'sad_number')) {
                $table->string('sad_number')->nullable()->after('boe');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'vehicle_number')) {
                $table->string('vehicle_number')->nullable()->after('sad_number');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'regime')) {
                $table->string('regime')->nullable()->after('vehicle_number');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'destination')) {
                $table->string('destination')->nullable()->after('regime');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'destination_id')) {
                $table->unsignedBigInteger('destination_id')->nullable()->after('destination');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'route_id')) {
                $table->unsignedBigInteger('route_id')->nullable()->after('destination_id');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable()->after('route_id');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'manifest_date')) {
                $table->dateTime('manifest_date')->nullable()->after('long_route_id');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'agency')) {
                $table->string('agency')->nullable()->after('manifest_date');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'agent_contact')) {
                $table->string('agent_contact')->nullable()->after('agency');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'truck_number')) {
                $table->string('truck_number')->nullable()->after('agent_contact');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('truck_number');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable()->after('driver_name');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'status')) {
                $table->string('status')->default('PENDING')->after('affixing_date');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'allocation_point_id')) {
                $table->unsignedBigInteger('allocation_point_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('confirmed_affix_logs', 'affixed_by')) {
                $table->unsignedBigInteger('affixed_by')->nullable()->after('allocation_point_id');
            }

            // Add foreign keys
            if (!Schema::hasColumn('confirmed_affix_logs', 'device_id')) {
                $table->unsignedBigInteger('device_id')->after('id')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confirmed_affix_logs', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = [
                'boe', 'sad_number', 'vehicle_number', 'regime', 'destination',
                'destination_id', 'route_id', 'long_route_id', 'manifest_date',
                'agency', 'agent_contact', 'truck_number', 'driver_name',
                'affixing_date', 'status', 'allocation_point_id', 'affixed_by'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('confirmed_affix_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
