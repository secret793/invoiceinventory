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
        if (!Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_type'); // Ensure this is set correctly
                $table->string('type')->nullable();
                $table->string('serial_number')->unique();
                $table->string('batch_number');
                //$table->string('status')->nullable();
                $table->date('date_received')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Assuming you have a user relationship
                $table->foreignId('distribution_point_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('allocation_point_id')->nullable()->constrained()->onDelete('set null'); // Added allocation point id
                $table->enum('status', ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST'])->nullable(); // Define as ENUM
                $table->string('sim_number')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
