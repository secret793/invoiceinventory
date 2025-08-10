<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransfersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_location')->constrained('distribution_points')->onDelete('cascade')->nullable();
            $table->foreignId('to_location')->constrained('distribution_points')->onDelete('cascade');
            $table->string('status')->default('PENDING');
            $table->integer('quantity')->unsigned()->check('quantity >= 0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
}
