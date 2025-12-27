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
        Schema::create('dispatch_finance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->unsignedBigInteger('assigned_to_agent_id')->nullable();
            $table->unsignedBigInteger('confirmed_affixed_id')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->dateTime('dispatch_date')->nullable();
            $table->decimal('total_amount_gmd', 12, 2)->nullable();
            $table->string('status', 20)->default('PENDING'); // PENDING, COMPLETED, CANCELLED
            $table->text('finance_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');

            // Indexes
            $table->index('receipt_id');
            $table->index('device_id');
            $table->index('status');
            $table->index('dispatch_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_finance_records');
    }
};
