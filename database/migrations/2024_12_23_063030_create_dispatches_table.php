<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDispatchesTable extends Migration
{
    public function up()
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Assuming users are dispatching
            $table->foreignId('distribution_point_id')->constrained()->onDelete('cascade');
            $table->json('devices'); // Store the dispatched devices (could be IDs or serialized data)
            $table->string('status')->default('pending'); // Dispatch status (pending, completed, canceled, etc.)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dispatches');
    }
}
