<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Add receipt_number column
            $table->string('receipt_number')->nullable();
            
            // Add overdue_days column
            $table->integer('overdue_days')->default(0);
            
            // Drop the old overdue_hours column if it exists
            if (Schema::hasColumn('device_retrievals', 'overdue_hours')) {
                $table->dropColumn('overdue_hours');
            }
        });
    }

    public function down()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn(['overdue_days', 'receipt_number']);
            $table->integer('overdue_hours')->default(0);
        });
    }
}; 