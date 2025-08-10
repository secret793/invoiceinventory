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
        Schema::create('other_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name'); // Field for the item name
            $table->integer('quantity'); // Field for the quantity
            $table->string('status'); // Field for the status
            $table->date('date_received'); // Field for the date received
            $table->string('type'); // Added type field
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade'); // Added added_by field
            $table->foreignId('distribution_point_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for distribution point
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_items');
    }
};
