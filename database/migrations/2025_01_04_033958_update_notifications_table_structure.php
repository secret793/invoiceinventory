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
        Schema::dropIfExists('notifications');
        
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->json('data')->nullable();
            $table->string('action')->nullable();
            $table->string('status')->default('unread');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index(['notifiable_id', 'notifiable_type']);
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
