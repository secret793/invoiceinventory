<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->index(['device_id', 'retrieval_status']);
            $table->index('overdue_days');
        });
    }

    public function down()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropIndex(['device_id', 'retrieval_status']);
            $table->dropIndex('overdue_days');
        });
    }
}; 