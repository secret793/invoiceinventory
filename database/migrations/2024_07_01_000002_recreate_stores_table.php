<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained()->onDelete('cascade');
            $table->string('device_type');
            $table->string('type')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('batch_number');
            $table->date('date_received');
            $table->enum('status', [
                'UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE',
                'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'REJECTED',
                'PENDING', 'RETRIEVED', 'ACTIVE'
            ])->default('UNCONFIGURED');
            $table->foreignId('distribution_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('allocation_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sim_number')->nullable();
            $table->string('sim_operator')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};


