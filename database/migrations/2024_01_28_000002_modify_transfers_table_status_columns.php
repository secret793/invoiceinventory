<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First add check constraints to ensure valid values
        DB::statement("ALTER TABLE transfers ADD CONSTRAINT transfers_status_check 
            CHECK (status IN ('PENDING', 'COMPLETED', 'CANCELLED'))");
            
        DB::statement("ALTER TABLE transfers ADD CONSTRAINT transfers_transfer_status_check 
            CHECK (transfer_status IN ('PENDING', 'COMPLETED', 'CANCELLED'))");
    }

    public function down()
    {
        // Remove the check constraints
        DB::statement('ALTER TABLE transfers DROP CONSTRAINT IF EXISTS transfers_status_check');
        DB::statement('ALTER TABLE transfers DROP CONSTRAINT IF EXISTS transfers_transfer_status_check');
    }
};
