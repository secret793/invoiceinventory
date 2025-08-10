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
        // First drop the existing table if it exists
        Schema::dropIfExists('notifications');

        // Create the notifications table with all required columns
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('action')->nullable();
            $table->string('status')->default('unread');
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
