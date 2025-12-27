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
        Schema::table('device_retrievals', function (Blueprint $table) {
            // Add archive columns to preserve financial records and maintain audit trail
            // When a device is returned, instead of deleting the record, we archive it
            // This preserves relationships with invoices and prevents data loss
            
            $table->boolean('is_archived')->default(false)->after('status');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->string('archive_reason')->nullable()->after('archived_at');
            
            // Add index for performance when filtering active (non-archived) records
            $table->index(['is_archived', 'archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_retrievals', function (Blueprint $table) {
            $table->dropIndex(['is_archived', 'archived_at']);
            $table->dropColumn(['is_archived', 'archived_at', 'archive_reason']);
        });
    }
};
