<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceRetrievalReport2TestTables extends Migration
{
    public function up()
    {
        // Create allocation points table first
        Schema::create('allocation_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create device retrieval report 2 table
        Schema::create('device_retrieval_report_2', function (Blueprint $table) {
            $table->id();
            $table->string('device_full_id');
            $table->string('boe')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->string('destination')->nullable();
            $table->string('retrieval_status');
            $table->foreignId('allocation_point_id')->constrained();
            $table->timestamp('retrieval_date')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_retrieval_report_2');
        Schema::dropIfExists('allocation_points');
    }
}