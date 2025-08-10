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
        Schema::table('data_entry_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('data_entry_assignments', 'notes')) {
                $table->text('notes')->nullable();
            }
            $table->text('return_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_entry_assignments', function (Blueprint $table) {
            $table->dropColumn(['return_note', 'notes']);
        });
    }
};
