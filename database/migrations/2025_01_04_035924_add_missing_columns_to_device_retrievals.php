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
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Add missing columns
            $table->timestamp('date')->nullable();
            $table->string('boe')->nullable();
            $table->string('sad_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('long_route_id')->nullable();
            $table->timestamp('manifest_date')->nullable();
            $table->string('destination')->nullable();
            $table->string('agency')->nullable();
            $table->string('agent_contact')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->timestamp('affixing_date')->nullable();

            // Add foreign keys
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('set null');
            $table->foreign('long_route_id')->references('id')->on('long_routes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['route_id']);
            $table->dropForeign(['long_route_id']);

            // Drop columns
            $table->dropColumn([
                'date',
                'boe',
                'sad_number',
                'vehicle_number',
                'regime',
                'route_id',
                'long_route_id',
                'manifest_date',
                'destination',
                'agency',
                'agent_contact',
                'truck_number',
                'driver_name',
                'affixing_date'
            ]);
        });
    }
};
