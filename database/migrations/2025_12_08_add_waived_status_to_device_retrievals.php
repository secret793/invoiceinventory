<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add WAIVED status to payment_status enum in device_retrievals table
        DB::statement("ALTER TABLE device_retrievals MODIFY payment_status ENUM('PP', 'PD', 'WAIVED') DEFAULT 'PP' COMMENT 'PP: Pending Payment, PD: Paid, WAIVED: Admin Waived'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum without WAIVED
        DB::statement("ALTER TABLE device_retrievals MODIFY payment_status ENUM('PP', 'PD') DEFAULT 'PP'");
    }
};
