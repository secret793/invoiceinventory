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
        Schema::table('invoices', function (Blueprint $table) {
            // Add missing fields to invoices table to match OverdueBillsAction modal requirements
            if (!Schema::hasColumn('invoices', 'customs_post')) {
                $table->string('customs_post')->nullable()->after('agent');
            }
            
            if (!Schema::hasColumn('invoices', 'consignee')) {
                $table->string('consignee')->nullable()->after('customs_post');
            }
            
            if (!Schema::hasColumn('invoices', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('consignee');
            }
            
            if (!Schema::hasColumn('invoices', 'departure')) {
                $table->string('departure')->nullable()->after('driver_name');
            }
            
            if (!Schema::hasColumn('invoices', 'destination')) {
                $table->string('destination')->nullable()->after('departure');
            }
            
            if (!Schema::hasColumn('invoices', 'asset_number')) {
                $table->string('asset_number')->nullable()->after('device_number');
            }
            
            if (!Schema::hasColumn('invoices', 'signature')) {
                $table->text('signature')->nullable()->after('received_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = ['customs_post', 'consignee', 'driver_name', 'departure', 'destination', 'asset_number', 'signature'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
