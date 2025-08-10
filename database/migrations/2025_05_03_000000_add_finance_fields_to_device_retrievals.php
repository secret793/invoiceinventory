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
        // First, ensure the users table has the proper index
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'id')) {
            return;
        }

        // Check if the id column in users table is properly indexed
        $indexExists = DB::select("SHOW INDEX FROM users WHERE Column_name = 'id' AND Key_name = 'PRIMARY'");

        if (empty($indexExists)) {
            // Add primary key to users table if it doesn't exist
            Schema::table('users', function (Blueprint $table) {
                $table->primary('id');
            });
        }

        // Now add the columns to device_retrievals
        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'finance_approval_date')) {
                $table->timestamp('finance_approval_date')->nullable();
            }

            if (!Schema::hasColumn('device_retrievals', 'finance_notes')) {
                $table->text('finance_notes')->nullable();
            }

            if (!Schema::hasColumn('device_retrievals', 'receipt_number')) {
                $table->string('receipt_number')->nullable();
            }
        });

        // Add the foreign key separately to handle potential issues
        if (!Schema::hasColumn('device_retrievals', 'finance_approved_by')) {
            Schema::table('device_retrievals', function (Blueprint $table) {
                $table->unsignedBigInteger('finance_approved_by')->nullable();
            });

            // Try to add the foreign key constraint
            try {
                Schema::table('device_retrievals', function (Blueprint $table) {
                    $table->foreign('finance_approved_by')
                          ->references('id')
                          ->on('users')
                          ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Log the error but continue with the migration
                \Log::error('Failed to add foreign key constraint: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Drop foreign key first if it exists
            if (Schema::hasColumn('device_retrievals', 'finance_approved_by')) {
                // Check if the foreign key constraint exists
                $foreignKeyExists = DB::select("
                    SELECT * FROM information_schema.TABLE_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'device_retrievals'
                    AND CONSTRAINT_NAME = 'device_retrievals_finance_approved_by_foreign'
                ");

                if (!empty($foreignKeyExists)) {
                    $table->dropForeign(['finance_approved_by']);
                }

                $table->dropColumn('finance_approved_by');
            }

            if (Schema::hasColumn('device_retrievals', 'finance_approval_date')) {
                $table->dropColumn('finance_approval_date');
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
