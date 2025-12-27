<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllocationPointIdToDevicesTable extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('allocation_point_id')->nullable()->constrained()->onDelete('set null'); // Add the foreign key
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['allocation_point_id']); // Drop the foreign key
            $table->dropColumn('allocation_point_id'); // Drop the column
        });
    }
}