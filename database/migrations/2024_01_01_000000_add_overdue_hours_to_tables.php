<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->integer('overdue_hours')->default(0)->after('manifest_date');
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->integer('overdue_hours')->default(0)->after('manifest_date');
        });
    }

    public function down()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn('overdue_hours');
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn('overdue_hours');
        });
    }
};
