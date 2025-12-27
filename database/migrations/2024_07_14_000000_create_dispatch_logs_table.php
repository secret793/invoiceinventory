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
        Schema::create('dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('data_entry_assignment_id');
            $table->unsignedBigInteger('dispatched_by');
            $table->timestamp('dispatched_at');
            $table->json('details')->nullable();
            $table->timestamps();
            
            // We'll add foreign key constraints in a separate migration
            // after all tables are created
            
            // Add indexes for performance
            $table->index('device_id');
            $table->index('data_entry_assignment_id');
            $table->index('dispatched_by');
            $table->index('dispatched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_logs');
    }
};
