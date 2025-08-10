<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('to_allocation_point_id')->nullable()->after('to_location')
                  ->constrained('allocation_points')->onDelete('cascade');
            $table->enum('transfer_type', ['DISTRIBUTION', 'ALLOCATION'])
                  ->default('DISTRIBUTION')->after('status');
        });
    }

    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['to_allocation_point_id']);
            $table->dropColumn(['to_allocation_point_id', 'transfer_type']);
        });
    }
}; 