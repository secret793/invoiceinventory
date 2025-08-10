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
        Schema::create('device_retrievals', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->unsignedBigInteger('device_id');
            $table->string('boe_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->string('destination')->nullable();
            $table->string('agency')->nullable();
            $table->string('agent_contact')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_retrievals');
    }
};
