<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First convert to string to modify
        DB::statement("ALTER TABLE devices MODIFY status VARCHAR(255)");
        
        // Then convert back to ENUM with updated values
        DB::statement("ALTER TABLE devices MODIFY status ENUM(
            'UNCONFIGURED',
            'CONFIGURED',
            'ONLINE',
            'OFFLINE',
            'DAMAGED',
            'FIXED',
            'LOST',
            'RECEIVED'
        ) NOT NULL DEFAULT 'UNCONFIGURED'");
    }

    public function down()
    {
        // Convert back to original ENUM
        DB::statement("ALTER TABLE devices MODIFY status ENUM(
            'UNCONFIGURED',
            'CONFIGURED',
            'ONLINE',
            'OFFLINE',
            'DAMAGED',
            'FIXED',
            'LOST'
        ) NOT NULL DEFAULT 'UNCONFIGURED'");
    }
}; 