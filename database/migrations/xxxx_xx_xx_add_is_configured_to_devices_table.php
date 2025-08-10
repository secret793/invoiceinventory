<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('is_configured')->default(true)->after('sim_number');
        });

        // Update existing records
        DB::statement('UPDATE devices SET is_configured = CASE 
            WHEN status = "NOT_CONFIGURED" THEN false 
            WHEN sim_number IS NULL THEN false 
            ELSE true 
            END');
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('is_configured');
        });
    }
}; 