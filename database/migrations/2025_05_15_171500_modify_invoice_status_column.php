<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyInvoiceStatusColumn extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the existing status column
            if (Schema::hasColumn('invoices', 'status')) {
                $table->dropColumn('status');
            }
        });

        // Recreate the status column with ENUM type
        DB::statement("ALTER TABLE invoices ADD COLUMN status ENUM('PP', 'PD', 'RJ') NOT NULL DEFAULT 'PP' COMMENT 'PP: Pending, PD: Paid, RJ: Rejected'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Revert to a simple string status column if needed
            if (Schema::hasColumn('invoices', 'status')) {
                $table->dropColumn('status');
            }
            $table->string('status')->nullable();
        });
    }
}
