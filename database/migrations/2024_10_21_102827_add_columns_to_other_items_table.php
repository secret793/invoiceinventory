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
        Schema::table('other_items', function (Blueprint $table) {
            $table->boolean('is_distributed')->default(false);
            $table->boolean('is_allocated')->default(false);
            $table->boolean('is_assigned')->default(false);
            // Add any other columns you need here
            // For example:
            // $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_items', function (Blueprint $table) {
            $table->dropColumn('is_distributed');
            $table->dropColumn('is_allocated');
            $table->dropColumn('is_assigned');
            // Drop any other columns added in the up() method
        });
    }
};
