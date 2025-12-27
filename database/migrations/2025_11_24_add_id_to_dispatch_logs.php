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
        Schema::table('dispatch_logs', function (Blueprint $table) {
            // Add id column as primary key if it doesn't exist
            if (!Schema::hasColumn('dispatch_logs', 'id')) {
                $table->id()->first();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dispatch_logs', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_logs', 'id')) {
                $table->dropColumn('id');
            }
        });
    }
};
