<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, ensure all existing status values are in proper format
        DB::table('transfers')->whereNotNull('status')->update(['status' => DB::raw("UPPER(status)")]);
        DB::table('transfers')->whereNotNull('transfer_status')->update(['transfer_status' => DB::raw("UPPER(transfer_status)")]);

        // Modify the columns
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('status', 20)->change();
            $table->string('transfer_status', 20)->change();
        });
    }

    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('status')->change();
            $table->string('transfer_status')->change();
        });
    }
};