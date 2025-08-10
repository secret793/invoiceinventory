<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, let's check current status values
        $currentStatuses = DB::table('devices')
            ->select('status')
            ->distinct()
            ->pluck('status');

        // Update any incompatible statuses to 'ONLINE' before modifying the enum
        DB::table('devices')
            ->whereNotIn('status', ['ONLINE', 'OFFLINE', 'PENDING'])
            ->update(['status' => 'ONLINE']);

        // Now safely modify the enum with both RETRIEVED and RECEIVED
        DB::statement("ALTER TABLE devices MODIFY COLUMN status ENUM('ONLINE', 'OFFLINE', 'PENDING', 'RETRIEVED', 'RECEIVED') NOT NULL DEFAULT 'ONLINE'");

        // Log the changes for debugging
        \Log::info('Device status migration', [
            'previous_statuses' => $currentStatuses,
            'new_enum' => "ENUM('ONLINE', 'OFFLINE', 'PENDING', 'RETRIEVED', 'RECEIVED')"
        ]);
    }

    public function down()
    {
        // First update any 'RETRIEVED' or 'RECEIVED' status to 'ONLINE'
        DB::table('devices')
            ->whereIn('status', ['RETRIEVED', 'RECEIVED'])
            ->update(['status' => 'ONLINE']);

        // Then modify the enum back
        DB::statement("ALTER TABLE devices MODIFY COLUMN status ENUM('ONLINE', 'OFFLINE', 'PENDING') NOT NULL DEFAULT 'ONLINE'");
    }
}; 