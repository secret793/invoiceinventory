<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('previous_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancellation_reason', 'cancelled_at', 'cancelled_by', 'previous_status']);
        });
    }
};
