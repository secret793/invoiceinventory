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
            if (!Schema::hasColumn('assign_to_agents', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable();
                $table->foreign('long_route_id')->references('id')->on('long_routes')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (Schema::hasColumn('assign_to_agents', 'long_route_id')) {
                $table->dropForeign(['long_route_id']);
                $table->dropColumn('long_route_id');
            }
        });
    }
};
