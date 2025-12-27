<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'transfer_status')) {
                $table->string('transfer_status')->nullable()->default('PENDING')->after('status');
            }
            if (!Schema::hasColumn('transfers', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()->after('transfer_status');
            }
            if (!Schema::hasColumn('transfers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            }
        });
    }

    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_status',
                'cancellation_reason',
                'cancelled_at'
            ]);
        });
    }
};
