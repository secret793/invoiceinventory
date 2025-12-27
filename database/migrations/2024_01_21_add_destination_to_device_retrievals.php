<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'destination')) {
                $table->string('destination')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn('destination');
        });
    }
}; 