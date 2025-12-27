<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDistributedToOtherItemsTable extends Migration
{
    public function up()
    {
        Schema::table('other_items', function (Blueprint $table) {
            $table->boolean('is_distributed')->default(0);
        });
    }

    public function down()
    {
        Schema::table('other_items', function (Blueprint $table) {
            $table->dropColumn('is_distributed');
        });
    }
} 