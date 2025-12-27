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
        Schema::table('device_retrieval_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('returned_by')->nullable()->after('retrieved_by');
            $table->foreign('returned_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['returned_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrieval_logs', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropIndex(['returned_by']);
            $table->dropColumn('returned_by');
        });
    }
};
