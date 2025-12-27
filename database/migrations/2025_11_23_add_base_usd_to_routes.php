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
        Schema::table('routes', function (Blueprint $table) {
            if (!Schema::hasColumn('routes', 'base_usd_amount')) {
                $table->decimal('base_usd_amount', 10, 2)->nullable()->after('amount');
            }
        });

        Schema::table('long_routes', function (Blueprint $table) {
            if (!Schema::hasColumn('long_routes', 'base_usd_amount')) {
                $table->decimal('base_usd_amount', 10, 2)->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            if (Schema::hasColumn('routes', 'base_usd_amount')) {
                $table->dropColumn('base_usd_amount');
            }
        });

        Schema::table('long_routes', function (Blueprint $table) {
            if (Schema::hasColumn('long_routes', 'base_usd_amount')) {
                $table->dropColumn('base_usd_amount');
            }
        });
    }
};
