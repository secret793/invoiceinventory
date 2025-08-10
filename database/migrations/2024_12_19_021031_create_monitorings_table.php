<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('boe_number');
            $table->string('vehicle_number');
            $table->string('regime');
            $table->string('destination');
            $table->string('agency');
            $table->string('agent_contact');
            $table->string('truck_number');
            $table->string('driver_name');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};
