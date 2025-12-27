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
        Schema::create('waiver_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_retrieval_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('admin_user_id');
            $table->text('reason');
            $table->integer('original_overstay_days');
            $table->decimal('original_amount', 10, 2);
            $table->timestamps();

            // Foreign keys
            $table->foreign('device_retrieval_id')
                ->references('id')
                ->on('device_retrievals')
                ->onDelete('cascade');

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('set null');

            $table->foreign('admin_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('device_retrieval_id');
            $table->index('admin_user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiver_history');
    }
};
