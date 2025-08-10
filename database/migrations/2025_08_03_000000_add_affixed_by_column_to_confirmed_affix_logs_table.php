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
        Schema::table('confirmed_affix_logs', function (Blueprint $table) {
            $table->integer('affixed_by')->nullable()->after('allocation_point_id');
            $table->foreign('affixed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confirmed_affix_logs', function (Blueprint $table) {
            $table->dropForeign(['affixed_by']);
            $table->dropColumn('affixed_by');
        });
    }
};
