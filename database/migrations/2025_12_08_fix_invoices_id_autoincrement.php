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
        // Fix the id column to have auto-increment if it doesn't
        // First, drop foreign key constraints
        DB::statement('ALTER TABLE waiver_history DROP FOREIGN KEY waiver_history_invoice_id_foreign');
        
        // Now modify the invoices id column
        if (Schema::hasColumn('invoices', 'id')) {
            DB::statement('ALTER TABLE invoices MODIFY id BIGINT UNSIGNED AUTO_INCREMENT');
        }
        
        // Re-add the foreign key constraint
        DB::statement('ALTER TABLE waiver_history ADD CONSTRAINT waiver_history_invoice_id_foreign 
                      FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse this operation
    }
};
