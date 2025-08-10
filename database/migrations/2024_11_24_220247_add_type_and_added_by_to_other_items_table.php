<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeAndAddedByToOtherItemsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('other_items', function (Blueprint $table) {
            $table->string('type')->after('date_received'); // Add type field
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('cascade')->after('type'); // Make added_by nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_items', function (Blueprint $table) {
            $table->dropForeign(['added_by']); // Drop foreign key constraint
            $table->dropColumn('added_by'); // Drop added_by field
            $table->dropColumn('type'); // Drop type field
        });
    }
}
