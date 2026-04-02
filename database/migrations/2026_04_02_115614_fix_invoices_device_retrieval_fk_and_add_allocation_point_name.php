<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes three data-integrity issues:
     *
     * 1. Adds `allocation_point_name` (snapshot) to invoices so that the
     *    allocation point is preserved even if the DeviceRetrieval is later
     *    deleted or its allocation_point_id changes.
     *
     * 2. Nulls out any `device_retrieval_id` values on invoices that point
     *    to non-existent device_retrieval rows (orphaned FKs).  At the time
     *    of writing 77 such rows exist because the DeviceRetrievals were
     *    deleted without the constraint in place.
     *
     * 3. Makes `device_retrieval_id` nullable and adds a proper FK with
     *    ON DELETE SET NULL so that future deletions of DeviceRetrieval rows
     *    automatically null the reference on the invoice instead of creating
     *    orphaned IDs.
     */
    public function up(): void
    {
        // -----------------------------------------------------------------
        // Step 1: Add allocation_point_name snapshot column if not present
        // -----------------------------------------------------------------
        if (!Schema::hasColumn('invoices', 'allocation_point_name')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('allocation_point_name')->nullable()->after('departure');
            });
        }

        // -----------------------------------------------------------------
        // Step 2: Backfill allocation_point_name from the linked relation
        // -----------------------------------------------------------------
        DB::statement("
            UPDATE invoices i
            INNER JOIN device_retrievals dr ON dr.id = i.device_retrieval_id
            INNER JOIN allocation_points ap ON ap.id = dr.allocation_point_id
            SET i.allocation_point_name = ap.name
            WHERE i.allocation_point_name IS NULL
        ");

        // -----------------------------------------------------------------
        // Step 3: Make device_retrieval_id nullable FIRST
        //         (required before we can SET NULL on orphaned rows)
        // -----------------------------------------------------------------
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('device_retrieval_id')->nullable()->change();
        });

        // -----------------------------------------------------------------
        // Step 4: Null out orphaned device_retrieval_id values
        //         (rows that reference deleted device_retrieval records)
        // -----------------------------------------------------------------
        DB::statement("
            UPDATE invoices i
            LEFT JOIN device_retrievals dr ON dr.id = i.device_retrieval_id
            SET i.device_retrieval_id = NULL
            WHERE i.device_retrieval_id IS NOT NULL
              AND dr.id IS NULL
        ");

        // -----------------------------------------------------------------
        // Step 5: Add FK constraint with ON DELETE SET NULL
        //         (only add if it does not already exist)
        // -----------------------------------------------------------------
        $fkExists = DB::select("
            SELECT COUNT(*) as cnt
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'invoices'
              AND REFERENCED_TABLE_NAME = 'device_retrievals'
        ")[0]->cnt > 0;

        if (!$fkExists) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('device_retrieval_id')
                      ->references('id')
                      ->on('device_retrievals')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop FK if it exists
            $table->dropForeign(['device_retrieval_id']);

            // Make NOT NULL again (note: any rows with NULL will need a value first)
            $table->unsignedBigInteger('device_retrieval_id')->nullable(false)->change();

            // Drop snapshot column
            if (Schema::hasColumn('invoices', 'allocation_point_name')) {
                $table->dropColumn('allocation_point_name');
            }
        });
    }
};
