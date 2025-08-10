<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinanceApprovalFields extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update device_retrievals table
        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'finance_status')) {
                $table->enum('finance_status', ['PP', 'PD', 'RJ'])->default('PP')
                    ->comment('PP: Pending Payment, PD: Paid, RJ: Rejected');
            }
            
            if (!Schema::hasColumn('device_retrievals', 'finance_approved_by')) {
                $table->unsignedBigInteger('finance_approved_by')->nullable();
                $table->foreign('finance_approved_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
            
            if (!Schema::hasColumn('device_retrievals', 'finance_approved_at')) {
                $table->timestamp('finance_approved_at')->nullable();
            }
        });

        // Update invoices table to add more finance-related fields
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'finance_notes')) {
                $table->text('finance_notes')->nullable();
            }
            
            if (!Schema::hasColumn('invoices', 'finance_approved_by')) {
                $table->unsignedBigInteger('finance_approved_by')->nullable();
                $table->foreign('finance_approved_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
            
            if (!Schema::hasColumn('invoices', 'finance_approved_at')) {
                $table->timestamp('finance_approved_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            if (Schema::hasColumn('device_retrievals', 'finance_status')) {
                $table->dropColumn('finance_status');
            }
            
            if (Schema::hasColumn('device_retrievals', 'finance_approved_by')) {
                $table->dropForeign(['finance_approved_by']);
                $table->dropColumn('finance_approved_by');
            }
            
            if (Schema::hasColumn('device_retrievals', 'finance_approved_at')) {
                $table->dropColumn('finance_approved_at');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'finance_notes')) {
                $table->dropColumn('finance_notes');
            }
            
            if (Schema::hasColumn('invoices', 'finance_approved_by')) {
                $table->dropForeign(['finance_approved_by']);
                $table->dropColumn('finance_approved_by');
            }
            
            if (Schema::hasColumn('invoices', 'finance_approved_at')) {
                $table->dropColumn('finance_approved_at');
            }
        });
    }
}
