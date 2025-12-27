<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            // Remove any existing foreign key if it exists
            $table->dropForeign(['allocation_point_id']);
            
            // Add the foreign key with cascade delete
            $table->foreign('allocation_point_id')
                ->references('id')
                ->on('allocation_points')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropForeign(['allocation_point_id']);
        });
    }
};
