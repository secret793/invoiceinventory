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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique()->index(); // REC-YYYYMMDD-XXXX
            $table->dateTime('date'); // Timestamp
            $table->enum('consignment_nature', ['CN', 'FT', 'GC', 'OV']); // Containers, Tankers, Cargo, Vehicles
            $table->string('sad_number')->unique(); // Customs SAD number
            $table->unsignedBigInteger('route_id')->nullable();
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');
            $table->unsignedBigInteger('long_route_id')->nullable();
            $table->foreign('long_route_id')->references('id')->on('long_routes')->onDelete('cascade');
            $table->unsignedBigInteger('allocation_point_id');
            $table->foreign('allocation_point_id')->references('id')->on('allocation_points')->onDelete('cascade');
            $table->unsignedInteger('destination_id');
            $table->foreign('destination_id')->references('id')->on('destinations')->onDelete('cascade');
            $table->string('billing_unit')->nullable(); // 'Short' or 'Long' (auto-populated)
            $table->integer('moving_trucks')->default(0); // Number of trucks
            $table->decimal('base_unit_charge_usd', 10, 2)->nullable(); // From route
            $table->decimal('exchange_rate_used', 8, 4)->default(74.07); // Live rate at creation
            $table->decimal('unit_charge_gmd', 10, 2)->default(0); // Computed: USD * rate
            $table->decimal('total_charge_gmd', 12, 2)->default(0); // Computed: unit_gmd * trucks
            $table->string('agent_name'); // Transportation agent
            $table->string('agent_phone'); // Agent contact
            $table->text('consignee_details'); // Consignee info
            $table->text('shipper_details')->nullable(); // Shipper info
            $table->text('description_of_goods'); // What's being transported
            $table->integer('used')->default(0); // Decrements on dispatch (starts = moving_trucks)
            $table->integer('created_by')->nullable(); // Track who created the receipt
            $table->timestamps();
            
            // Indexes for performance
            $table->index('allocation_point_id');
            $table->index('route_id');
            $table->index('used'); // For filtering available receipts
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
