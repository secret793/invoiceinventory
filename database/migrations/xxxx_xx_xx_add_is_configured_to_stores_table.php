<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('is_configured')->default(true)->after('sim_number');
        });

        // Sync existing records
        DB::statement('UPDATE stores s 
            INNER JOIN devices d ON s.device_id = d.device_id 
            SET s.is_configured = d.is_configured');
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('is_configured');
        });
    }
}; 