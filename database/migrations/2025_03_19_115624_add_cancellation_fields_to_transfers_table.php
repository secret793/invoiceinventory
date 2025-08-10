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
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'original_allocation_point_id')) {
                $table->unsignedBigInteger('original_allocation_point_id')->nullable()->after('to_location');
            }
            if (!Schema::hasColumn('transfers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('transfers', 'transfer_status')) {
                $table->string('transfer_status')->default('PENDING');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn([
                'original_allocation_point_id',
                'cancelled_at',
                'transfer_status'
            ]);
        });
    }
};
