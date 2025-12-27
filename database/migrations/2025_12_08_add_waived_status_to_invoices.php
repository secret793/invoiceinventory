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
        Schema::table('invoices', function (Blueprint $table) {
            // Add waived_by and waived_at columns
            if (!Schema::hasColumn('invoices', 'waived_by')) {
                $table->unsignedBigInteger('waived_by')->nullable()->after('status');
                $table->foreign('waived_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('invoices', 'waived_at')) {
                $table->dateTime('waived_at')->nullable()->after('waived_by');
            }
        });

        // Update status enum to include WAIVED
        DB::statement("ALTER TABLE invoices MODIFY status ENUM('PP', 'PD', 'RJ', 'WAIVED') DEFAULT 'PP' COMMENT 'PP: Pending Payment, PD: Paid, RJ: Rejected, WAIVED: Admin Waived'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'waived_by')) {
                $table->dropForeign(['waived_by']);
                $table->dropColumn('waived_by');
            }

            if (Schema::hasColumn('invoices', 'waived_at')) {
                $table->dropColumn('waived_at');
            }
        });

        // Revert status enum
        DB::statement("ALTER TABLE invoices MODIFY status ENUM('PP', 'PD', 'RJ') DEFAULT 'PP'");
    }
};
