<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();
    }

    protected function setupTestDatabase(): void
    {
        // Create necessary tables for testing
        if (!Schema::hasTable('allocation_points')) {
            Schema::create('allocation_points', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('data_entry_assignments')) {
            Schema::create('data_entry_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('allocation_point_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('routes')) {
            Schema::create('routes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('long_routes')) {
            Schema::create('long_routes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status')->default('ONLINE');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('monitorings')) {
            Schema::create('monitorings', function (Blueprint $table) {
                $table->id();
                $table->dateTime('date');
                $table->dateTime('current_date')->nullable();
                $table->unsignedBigInteger('device_id');
                $table->string('boe');
                $table->string('vehicle_number');
                $table->string('regime');
                $table->string('destination');
                $table->timestamps();
            });
        }

        // Add foreign key constraints
        Schema::table('data_entry_assignments', function (Blueprint $table) {
            $table->foreign('allocation_point_id')
                  ->references('id')
                  ->on('allocation_points')
                  ->onDelete('cascade');
        });

        Schema::table('monitorings', function (Blueprint $table) {
            $table->foreign('device_id')
                  ->references('id')
                  ->on('devices')
                  ->onDelete('cascade');
        });
    }
}
