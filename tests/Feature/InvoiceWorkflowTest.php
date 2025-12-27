<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceRetrieval;
use App\Models\User;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_workflow()
    {
        // 1. Create a device
        $device = Device::factory()->create([
            'device_id' => 'TEST-001',
            'serial_number' => 'SN12345',
            'status' => 'ACTIVE',
        ]);

        // 2. Create a device retrieval with overstay days
        $deviceRetrieval = DeviceRetrieval::create([
            'device_id' => $device->id,
            'date' => Carbon::now()->subDays(5),
            'affixing_date' => Carbon::now()->subDays(5),
            'boe' => 'BOE12345',
            'sad_number' => 'SAD12345',
            'vehicle_number' => 'VEH001',
            'regime' => 'TRANSIT',
            'destination' => 'Test Destination',
            'agent' => 'Test Agent',
            'agent_contact' => '1234567890',
            'status' => 'ACTIVE',
            'retrieval_status' => 'PENDING',
            'overstay_days' => 3, // 3 days overstay
            'overstay_amount' => 3000, // D1000 per day
            'payment_status' => 'PP', // Pending Payment
        ]);

        // Verify the device retrieval was created with correct overstay values
        $this->assertEquals(3, $deviceRetrieval->overstay_days);
        $this->assertEquals(3000, $deviceRetrieval->overstay_amount);
        $this->assertEquals('PP', $deviceRetrieval->payment_status);

        echo "✓ Created device and device retrieval with overstay days\n";
    }
}