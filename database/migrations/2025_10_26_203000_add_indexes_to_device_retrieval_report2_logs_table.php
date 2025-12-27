<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            // Add indexes for commonly filtered/searched columns
            $table->index('retrieval_status');
            $table->index('action_type');
            $table->index('device_full_id');
            $table->index('boe');
            $table->index('vehicle_number');
            $table->index('retrieval_date');
            $table->index('returned_at');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            $table->dropIndex(['retrieval_status']);
            $table->dropIndex(['action_type']);
            $table->dropIndex(['device_full_id']);
            $table->dropIndex(['boe']);
            $table->dropIndex(['vehicle_number']);
            $table->dropIndex(['retrieval_date']);
            $table->dropIndex(['returned_at']);
            $table->dropIndex(['created_at']);
        });
    }
};