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
        // Drop the existing table
        Schema::dropIfExists('notifications');

        // Recreate with correct column types
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->string('notifiable_id'); // Changed to string to handle both UUIDs and numeric IDs
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('user_id')->nullable(); // Changed to string to handle UUIDs
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
