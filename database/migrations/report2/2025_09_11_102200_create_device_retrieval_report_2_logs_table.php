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
        Schema::create('device_retrieval_report_2_logs', function (Blueprint $table) {
            $table->id();
            
            // Essential device information
            $table->unsignedBigInteger('device_id');
            $table->string('device_full_id')->nullable(); // Full device identifier for display
            $table->string('boe')->nullable();
            $table->string('vehicle_number')->nullable();
            
            // Location and routing
            $table->string('destination')->nullable(); // For filtering by destination permissions
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            
            // Status tracking
            $table->enum('retrieval_status', ['NOT_RETRIEVED', 'RETRIEVED', 'RETURNED'])->default('NOT_RETRIEVED');
            $table->enum('action_type', ['RETRIEVED', 'RETURNED'])->nullable();
            
            // User tracking
            $table->unsignedInteger('retrieved_by')->nullable();
            $table->unsignedInteger('returned_by')->nullable();
            
            // Timestamp tracking
            $table->datetime('retrieval_date')->nullable();
            $table->datetime('returned_at')->nullable();
            
            // Financial information
            $table->integer('overstay_days')->default(0);
            $table->decimal('overstay_amount', 10, 2)->default(0);
            
            $table->timestamps();
            
            // Foreign key constraints (commented out due to compatibility issues)
            // $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            // $table->foreign('retrieved_by')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('returned_by')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['device_id', 'action_type']);
            $table->index(['retrieval_status']);
            $table->index(['destination']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_retrieval_report_2_logs');
    }
};
