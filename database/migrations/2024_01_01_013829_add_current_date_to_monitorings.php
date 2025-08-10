<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dateTime('current_date')->nullable()->after('date');
        });
    }

    public function down()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn('current_date');
        });
    }
};
