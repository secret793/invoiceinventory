<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('monitoring');

        Schema::create('monitoring', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['device_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring');
    }
};
