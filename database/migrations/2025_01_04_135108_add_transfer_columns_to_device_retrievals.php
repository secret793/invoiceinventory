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
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Add columns for transfer destination
            $table->foreignId('distribution_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('allocation_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transfer_type')->nullable(); // 'distribution' or 'allocation'
            $table->string('transfer_status')->nullable(); // 'pending', 'completed'
            $table->timestamp('transfer_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropForeign(['distribution_point_id']);
            $table->dropForeign(['allocation_point_id']);
            $table->dropColumn([
                'distribution_point_id',
                'allocation_point_id',
                'transfer_type',
                'transfer_status',
                'transfer_date'
            ]);
        });
    }
};
