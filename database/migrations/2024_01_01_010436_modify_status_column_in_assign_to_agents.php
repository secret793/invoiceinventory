<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            // First drop the existing status column
            $table->dropColumn('status');
        });

        Schema::table('assign_to_agents', function (Blueprint $table) {
            // Recreate the status column with ENUM that includes all possible values
            $table->enum('status', [
                'PENDING',
                'ASSIGNED',
                'AFFIXED',
                'RETRIEVED',
                'TRANSFERRED'
            ])->default('PENDING')->after('driver_name');
        });
    }

    public function down()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('status')->default('PENDING')->after('driver_name');
        });
    }
};
