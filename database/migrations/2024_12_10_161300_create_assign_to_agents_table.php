<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assign_to_agents', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->string('boe_number');
            $table->string('vehicle_number');
            $table->string('regime');
            $table->string('destination');
            $table->string('agency')->nullable();
            $table->string('agent_contact')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assign_to_agents');
    }
};
