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
        Schema::dropIfExists('confirmed_affixeds');

        Schema::create('confirmed_affixeds', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date')->nullable()->useCurrent();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('boe')->nullable();
            $table->string('sad_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->string('destination')->nullable();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->foreignId('long_route_id')->nullable()->constrained('long_routes')->nullOnDelete();
            $table->date('manifest_date')->nullable();
            $table->string('agency')->nullable();
            $table->string('agent_contact')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->dateTime('affixing_date')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['route_id', 'long_route_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmed_affixeds');
    }
};
