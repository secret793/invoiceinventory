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
            // Add overstay_amount column (decimal with 2 decimal places)
            $table->decimal('overstay_amount', 10, 2)->default(0.00)->after('overstay_days');
            
            // Add payment_status column (enum with 'PP' for Pending Payment and 'PD' for Paid)
            $table->enum('payment_status', ['PP', 'PD'])->default('PP')->after('overstay_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn(['overstay_amount', 'payment_status']);
        });
    }
};