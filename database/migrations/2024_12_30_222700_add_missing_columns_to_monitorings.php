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
            if (!Schema::hasColumn('monitorings', 'sad_number')) {
                $table->string('sad_number')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'boe_number')) {
                $table->string('boe_number')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'vehicle_number')) {
                $table->string('vehicle_number')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'regime')) {
                $table->string('regime')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            }
            if (!Schema::hasColumn('monitorings', 'long_route_id')) {
                $table->foreignId('long_route_id')->nullable()->constrained('long_routes')->nullOnDelete();
            }
            if (!Schema::hasColumn('monitorings', 'manifest_date')) {
                $table->date('manifest_date')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'destination')) {
                $table->string('destination')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'agency')) {
                $table->string('agency')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'agent_contact')) {
                $table->string('agent_contact')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'truck_number')) {
                $table->string('truck_number')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'driver_name')) {
                $table->string('driver_name')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn([
                'sad_number',
                'boe_number',
                'vehicle_number',
                'regime',
                'destination',
                'agency',
                'agent_contact',
                'truck_number',
                'driver_name',
                'affixing_date'
            ]);
            $table->dropConstrainedForeignId('route_id');
            $table->dropConstrainedForeignId('long_route_id');
            $table->dropColumn('manifest_date');
        });
    }
};
