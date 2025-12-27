<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First convert to string to modify
        DB::statement("ALTER TABLE transfers MODIFY status ENUM(
            'PENDING',
            'COMPLETED',
            'REJECTED'
        ) NOT NULL DEFAULT 'PENDING'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE transfers MODIFY status VARCHAR(255)");
    }
}; 