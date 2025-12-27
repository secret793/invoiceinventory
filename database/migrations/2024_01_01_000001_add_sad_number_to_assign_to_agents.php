<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->string('sad_number')->nullable()->after('boe');
        });
    }

    public function down()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropColumn('sad_number');
        });
    }
};
