<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDestinationIdToConfirmedAffixedsTable extends Migration
{
    public function up()
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            $table->unsignedBigInteger('destination_id')->nullable()->after('long_route_id'); // Adjust the position as needed
        });
    }

    public function down()
    {
        Schema::table('confirmed_affixeds', function (Blueprint $table) {
            $table->dropColumn('destination_id');
        });
    }
}