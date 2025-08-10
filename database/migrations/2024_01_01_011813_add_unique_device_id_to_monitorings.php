<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Monitoring;

return new class extends Migration
{
    public function up()
    {
        // First, remove duplicates keeping only the latest record
        $devices = Monitoring::select('device_id')
            ->groupBy('device_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($devices as $device) {
            $duplicates = Monitoring::where('device_id', $device->device_id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Keep the first (latest) record and delete the rest
            $duplicates->skip(1)->each(function ($duplicate) {
                $duplicate->delete();
            });
        }

        // Now add the unique index
        Schema::table('monitorings', function (Blueprint $table) {
            $table->unique('device_id', 'unique_device_id_in_monitorings');
        });
    }

    public function down()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropUnique('unique_device_id_in_monitorings');
        });
    }
};
