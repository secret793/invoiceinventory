<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            // Drop the existing primary key if it exists
            $table->dropPrimary();
            
            // Modify the id column to be a proper auto-incrementing primary key
            $table->id()->first();
            
            // Add selected column
            $table->boolean('selected')->default(false)->after('id');
        });
    }

    public function down()
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropColumn('selected');
            $table->dropPrimary();
            $table->integer('id')->primary();
        });
    }
};
