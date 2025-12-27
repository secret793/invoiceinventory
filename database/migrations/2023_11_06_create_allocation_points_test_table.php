<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllocationPointsTestTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('allocation_points')) {
            Schema::create('allocation_points', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('allocation_points');
    }
}