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
            // Add columns without foreign key constraints
            if (!Schema::hasColumn('device_retrievals', 'finance_approval_date')) {
                $table->timestamp('finance_approval_date')->nullable();
            }
            
            if (!Schema::hasColumn('device_retrievals', 'finance_approved_by')) {
                $table->unsignedBigInteger('finance_approved_by')->nullable();
            }
            
            if (!Schema::hasColumn('device_retrievals', 'finance_notes')) {
                $table->text('finance_notes')->nullable();
            }
            
            if (!Schema::hasColumn('device_retrievals', 'receipt_number')) {
                $table->string('receipt_number')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            if (Schema::hasColumn('device_retrievals', 'finance_approval_date')) {
                $table->dropColumn('finance_approval_date');
            }
            
            if (Schema::hasColumn('device_retrievals', 'finance_approved_by')) {
                $table->dropColumn('finance_approved_by');
            }
            
            if (Schema::hasColumn('device_retrievals', 'finance_notes')) {
                $table->dropColumn('finance_notes');
            }
            
            if (Schema::hasColumn('device_retrievals', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
        });
    }
};