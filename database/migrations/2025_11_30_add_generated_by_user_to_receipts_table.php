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
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('generated_by_user')->nullable()->after('created_by');
            $table->foreign('generated_by_user')->references('id')->on('users')->onDelete('set null');
            $table->index('generated_by_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropForeign(['generated_by_user']);
            $table->dropIndex(['generated_by_user']);
            $table->dropColumn('generated_by_user');
        });
    }
};
