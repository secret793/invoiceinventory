<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The allocation_point_id column already exists from a previous migration
        // This migration ensures it's properly indexed and updates any existing records
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Add index to allocation_point_id for better performance
            $table->index('allocation_point_id');
        });
        
        // Update existing device_retrievals records to set allocation_point_id from confirmed_affixeds
        DB::statement('
            UPDATE device_retrievals dr
            INNER JOIN confirmed_affixeds ca ON dr.device_id = ca.device_id
            SET dr.allocation_point_id = ca.allocation_point_id
            WHERE dr.allocation_point_id IS NULL AND ca.allocation_point_id IS NOT NULL
        ');

        Schema::table('devices', function (Blueprint $table) {
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_type');
            $table->string('device_id')->unique();
            $table->string('serial_number')->unique();
            $table->string('batch_number');
            $table->date('date_received');
            $table->enum('status', ['ACTIVE', 'REJECTED', 'RETRIEVED', 'PENDING', 'RECEIVED'])->default('ACTIVE');
            $table->unsignedBigInteger('distribution_point_id')->nullable();
            $table->string('sim_number')->nullable();
            $table->string('sim_operator')->nullable();
            $table->boolean('is_configured')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('distribution_point_id')->references('id')->on('distribution_points')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropIndex(['allocation_point_id']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });

        Schema::dropIfExists('devices');
    }
};
