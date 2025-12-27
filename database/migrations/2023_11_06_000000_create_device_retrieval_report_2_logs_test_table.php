<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceRetrievalReport2LogsTestTable extends Migration
{
    public function up()
    {
        Schema::create('device_retrieval_report_2_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_full_id');
            $table->string('retrieval_status');
            $table->string('action_type');
            $table->dateTime('retrieval_date')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->string('boe')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('regime')->nullable();
            $table->string('destination')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->integer('overstay_days')->default(0);
            $table->decimal('overstay_amount', 10, 2)->default(0.00);
            $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('set null');
            $table->foreignId('allocation_point_id')->nullable()->constrained('allocation_points')->onDelete('set null');
            $table->foreignId('retrieved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('returned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_retrieval_report_2_logs');
    }
}