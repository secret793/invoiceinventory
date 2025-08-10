<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First check if the column exists
        if (!Schema::hasColumn('device_retrievals', 'retrieval_status')) {
            // Add the ENUM column
            DB::statement("ALTER TABLE device_retrievals ADD COLUMN retrieval_status ENUM('NOT_RETRIEVED', 'RETRIEVED') NOT NULL DEFAULT 'NOT_RETRIEVED'");
        }
    }

    public function down()
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropColumn('retrieval_status');
        });
    }
};