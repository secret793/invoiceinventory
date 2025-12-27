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
        // Add receipt_id to assign_to_agents table if not exists
        if (Schema::hasTable('assign_to_agents') && !Schema::hasColumn('assign_to_agents', 'receipt_id')) {
            Schema::table('assign_to_agents', function (Blueprint $table) {
                $table->unsignedBigInteger('receipt_id')->nullable()->after('driver_name');
                $table->foreign('receipt_id')->references('id')->on('receipts')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assign_to_agents') && Schema::hasColumn('assign_to_agents', 'receipt_id')) {
            Schema::table('assign_to_agents', function (Blueprint $table) {
                $table->dropForeign(['receipt_id']);
                $table->dropColumn('receipt_id');
            });
        }
    }
};
