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
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('boe')->nullable();
            $table->string('sad_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->string('destination')->nullable();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->datetime('current_time')->nullable();
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
            $table->enum('retrieval_status', ['NOT_RETRIEVED', 'RETRIEVED', 'RETURNED'])->default('NOT_RETRIEVED');
            $table->integer('overdue_hours')->nullable();
            $table->integer('overstay_days')->nullable();
            $table->decimal('overstay_amount', 10, 2)->nullable();
            $table->enum('payment_status', ['PP', 'PD'])->default('PP');
            $table->string('receipt_number')->nullable();
            $table->unsignedBigInteger('distribution_point_id')->nullable();
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            $table->unsignedBigInteger('retrieved_by')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->datetime('retrieval_date')->nullable();
            $table->datetime('returned_at')->nullable();
            $table->enum('action_type', ['RETRIEVED', 'RETURNED'])->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('destination_id')->references('id')->on('destinations')->onDelete('set null');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('set null');
            $table->foreign('long_route_id')->references('id')->on('long_routes')->onDelete('set null');
            $table->foreign('distribution_point_id')->references('id')->on('distribution_points')->onDelete('set null');
            $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->onDelete('set null');
            $table->foreign('retrieved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('returned_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for better performance
            $table->index(['retrieval_status', 'created_at']);
            $table->index(['action_type', 'created_at']);
            $table->index(['device_id', 'retrieval_status']);
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
