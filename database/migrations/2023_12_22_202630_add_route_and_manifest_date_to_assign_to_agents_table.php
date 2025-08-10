<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('assign_to_agents', 'route_id')) {
                $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('assign_to_agents', 'manifest_date')) {
                $table->date('manifest_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assign_to_agents', function (Blueprint $table) {
            if (Schema::hasColumn('assign_to_agents', 'route_id')) {
                $table->dropForeign(['route_id']);
                $table->dropColumn('route_id');
            }
            if (Schema::hasColumn('assign_to_agents', 'manifest_date')) {
                $table->dropColumn('manifest_date');
            }
        });
    }
};
