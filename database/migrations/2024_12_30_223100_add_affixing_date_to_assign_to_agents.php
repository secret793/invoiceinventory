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
        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('assign_to_agents', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            $table->dropColumn('affixing_date');
        });
    }
};
