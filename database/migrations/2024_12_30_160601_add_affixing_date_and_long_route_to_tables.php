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
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            if (!Schema::hasColumn('confirmed_affixeds', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable();
            }
            if (!Schema::hasColumn('confirmed_affixeds', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable();
                $table->foreign('long_route_id')->references('id')->on('long_routes');
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable();
            }
            if (!Schema::hasColumn('device_retrievals', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable();
                $table->foreign('long_route_id')->references('id')->on('long_routes');
            }
        });

        Schema::table('monitorings', function (Blueprint $table) {
            if (!Schema::hasColumn('monitorings', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable();
            }
            if (!Schema::hasColumn('monitorings', 'long_route_id')) {
                $table->unsignedBigInteger('long_route_id')->nullable();
                $table->foreign('long_route_id')->references('id')->on('long_routes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            if (Schema::hasColumn('confirmed_affixeds', 'long_route_id')) {
                $table->dropForeign(['long_route_id']);
            }
            if (Schema::hasColumn('confirmed_affixeds', 'affixing_date')) {
                $table->dropColumn('affixing_date');
            }
            if (Schema::hasColumn('confirmed_affixeds', 'long_route_id')) {
                $table->dropColumn('long_route_id');
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (Schema::hasColumn('device_retrievals', 'long_route_id')) {
                $table->dropForeign(['long_route_id']);
            }
            if (Schema::hasColumn('device_retrievals', 'affixing_date')) {
                $table->dropColumn('affixing_date');
            }
            if (Schema::hasColumn('device_retrievals', 'long_route_id')) {
                $table->dropColumn('long_route_id');
            }
        });

        Schema::table('monitorings', function (Blueprint $table) {
            if (Schema::hasColumn('monitorings', 'long_route_id')) {
                $table->dropForeign(['long_route_id']);
            }
            if (Schema::hasColumn('monitorings', 'affixing_date')) {
                $table->dropColumn('affixing_date');
            }
            if (Schema::hasColumn('monitorings', 'long_route_id')) {
                $table->dropColumn('long_route_id');
            }
        });
    }
};
