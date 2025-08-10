<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'original_status')) {
                $table->string('original_status')->nullable()->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('original_status');
        });
    }
}; 