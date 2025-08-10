<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddComprehensiveFieldsToInvoicesTable_20240514_1 extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add missing fields
            if (!Schema::hasColumn('invoices', 'sad_boe')) {
                $table->string('sad_boe')->nullable()->after('reference_number');
            }
            
            if (!Schema::hasColumn('invoices', 'regime')) {
                $table->string('regime')->nullable()->after('sad_boe');
            }
            
            if (!Schema::hasColumn('invoices', 'agent')) {
                $table->string('agent')->nullable()->after('regime');
            }
            
            if (!Schema::hasColumn('invoices', 'route')) {
                $table->string('route')->nullable()->after('agent');
            }
            
            if (!Schema::hasColumn('invoices', 'overstay_days')) {
                $table->integer('overstay_days')->default(0)->after('route');
            }
            
            if (!Schema::hasColumn('invoices', 'penalty_amount')) {
                $table->decimal('penalty_amount', 10, 2)->nullable()->after('overstay_days');
            }
            
            if (!Schema::hasColumn('invoices', 'device_number')) {
                $table->string('device_number')->nullable()->after('penalty_amount');
            }
            
            if (!Schema::hasColumn('invoices', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('device_number');
            }
            
            if (!Schema::hasColumn('invoices', 'description')) {
                $table->text('description')->nullable()->after('total_amount');
            }
            
            if (!Schema::hasColumn('invoices', 'paid_by')) {
                $table->string('paid_by')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('invoices', 'received_by')) {
                $table->string('received_by')->nullable()->after('paid_by');
            }
            
            if (!Schema::hasColumn('invoices', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('received_by');
            }
            
            // Ensure status column exists
            if (!Schema::hasColumn('invoices', 'status')) {
                $table->enum('status', ['PP', 'PD', 'REJECTED'])->default('PP')->after('logo_path');
            }

            // Add foreign key for device retrieval
            if (!Schema::hasColumn('invoices', 'device_retrieval_id')) {
                $table->unsignedBigInteger('device_retrieval_id')->nullable()->after('status');
                $table->foreign('device_retrieval_id')
                      ->references('id')
                      ->on('device_retrievals')
                      ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumnIfExists([
                'sad_boe', 'regime', 'agent', 'route', 
                'overstay_days', 'penalty_amount', 'device_number', 
                'total_amount', 'description', 'paid_by', 
                'received_by', 'logo_path', 'status', 
                'device_retrieval_id'
            ]);
        });
    }
}
