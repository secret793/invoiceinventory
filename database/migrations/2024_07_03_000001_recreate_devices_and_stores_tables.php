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
        // Drop tables if they exist
        Schema::dropIfExists('stores');
        Schema::dropIfExists('devices');

        // Create devices table without foreign keys first
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_type');
            $table->string('type')->nullable();
            $table->string('device_id')->unique();
            $table->string('serial_number')->unique();
            $table->string('batch_number');
            $table->date('date_received');
            $table->enum('status', [
                'UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE',
                'DAMAGED', 'FIXED', 'LOST', 'RECEIVED', 'REJECTED',
                'PENDING', 'RETRIEVED', 'ACTIVE'
            ])->default('UNCONFIGURED');
            $table->unsignedBigInteger('distribution_point_id')->nullable();
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            $table->string('sim_number')->nullable();
            $table->string('sim_operator')->nullable();
            $table->boolean('is_configured')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create stores table without foreign keys first
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->unique();
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
            $table->unsignedBigInteger('distribution_point_id')->nullable();
            $table->unsignedBigInteger('allocation_point_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('sim_number')->nullable();
            $table->string('sim_operator')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add foreign keys to devices table
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasTable('distribution_points')) {
                $table->foreign('distribution_point_id')
                    ->references('id')
                    ->on('distribution_points')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('allocation_points')) {
                $table->foreign('allocation_point_id')
                    ->references('id')
                    ->on('allocation_points')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });

        // Add foreign keys to stores table
        Schema::table('stores', function (Blueprint $table) {
            $table->foreign('device_id')
                ->references('id')
                ->on('devices')
                ->onDelete('cascade');

            if (Schema::hasTable('distribution_points')) {
                $table->foreign('distribution_point_id')
                    ->references('id')
                    ->on('distribution_points')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('allocation_points')) {
                $table->foreign('allocation_point_id')
                    ->references('id')
                    ->on('allocation_points')
                    ->onDelete('set null');
            }

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
        Schema::dropIfExists('devices');
    }
};
