<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            // Add route_id column
            $table->unsignedBigInteger('route_id')->nullable()->after('regime');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('set null');
            
            // Keep regime column and add route_name column
            $table->string('route_name')->nullable()->after('route_id');
        });
    }

    public function down()
    {
        Schema::table('device_retrieval_report_2_logs', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
            $table->renameColumn('route_name', 'regime');
        });
    }
};