<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            $table->foreignId('allocation_point_id')->nullable()->after('driver_name')
                  ->constrained('allocation_points')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            $table->dropForeign(['allocation_point_id']);
            $table->dropColumn('allocation_point_id');
        });
    }
};