<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            if (!Schema::hasColumn('monitorings', 'note')) {
                $table->text('note')->nullable()->after('manifest_date');
            }
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            if (!Schema::hasColumn('device_retrievals', 'note')) {
                $table->text('note')->nullable()->after('manifest_date');
            }
        });
    }

    public function down()
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
