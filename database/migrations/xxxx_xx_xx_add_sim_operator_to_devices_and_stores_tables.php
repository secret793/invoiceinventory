<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('sim_operator')->nullable()->after('sim_number');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('sim_operator')->nullable()->after('sim_number');
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('sim_operator');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('sim_operator');
        });
    }
}; 