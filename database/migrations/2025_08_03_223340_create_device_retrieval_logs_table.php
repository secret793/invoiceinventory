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
        Schema::create('device_retrieval_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('device_id');
            $table->string('boe')->nullable();
            $table->string('sad_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->text('destination')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->timestamp('current_time')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('long_route_id')->nullable();
            $table->date('manifest_date')->nullable();
            $table->text('note')->nullable();
            $table->string('agency')->nullable();
            $table->string('agent_contact')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->date('affixing_date')->nullable();
            $table->string('status')->nullable();
            $table->string('retrieval_status')->nullable();
            $table->integer('overdue_hours')->default(0);
            $table->integer('overstay_days')->default(0);
            $table->decimal('overstay_amount', 15, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->string('receipt_number')->nullable();
            $table->unsignedBigInteger('distribution_point_id')->nullable();
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            $table->unsignedBigInteger('retrieved_by')->nullable(); // User who performed the retrieval/return action
            $table->timestamp('retrieval_date')->nullable(); // When the device was retrieved/returned
            $table->string('action_type')->nullable(); // 'RETRIEVED' or 'RETURNED'
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('set null');
            $table->foreign('long_route_id')->references('id')->on('long_routes')->onDelete('set null');
            $table->foreign('distribution_point_id')->references('id')->on('distribution_points')->onDelete('set null');
            $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->onDelete('set null');
            $table->foreign('retrieved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better query performance
            $table->index(['device_id', 'retrieval_status']);
            $table->index(['action_type', 'retrieval_date']);
            $table->index(['allocation_point_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_retrieval_logs');
    }
};
