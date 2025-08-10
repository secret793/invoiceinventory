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
            if (!Schema::hasColumn('device_retrievals', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            }
            if (!Schema::hasColumn('device_retrievals', 'long_route_id')) {
                $table->foreignId('long_route_id')->nullable()->constrained('long_routes')->nullOnDelete();
            }
            if (!Schema::hasColumn('device_retrievals', 'affixing_date')) {
                $table->dateTime('affixing_date')->nullable();
            }
            if (!Schema::hasColumn('device_retrievals', 'sad_number')) {
                $table->string('sad_number')->nullable();
            }
            if (!Schema::hasColumn('device_retrievals', 'boe')) {
                $table->string('boe')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_id');
            $table->dropConstrainedForeignId('long_route_id');
            $table->dropColumn([
                'affixing_date',
                'sad_number',
                'boe'
            ]);
        });
    }
};
