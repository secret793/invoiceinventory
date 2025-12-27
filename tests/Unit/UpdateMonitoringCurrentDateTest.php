<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Monitoring;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpdateMonitoringCurrentDateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-12-31 02:15:00');

        // Create necessary tables for testing
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('ONLINE');
            $table->timestamps();
        });

        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->dateTime('current_date')->nullable();
            $table->unsignedBigInteger('device_id');
            $table->string('boe');
            $table->string('vehicle_number');
            $table->string('regime');
            $table->string('destination');
            $table->timestamps();
            
            $table->foreign('device_id')
                  ->references('id')
                  ->on('devices')
                  ->onDelete('cascade');
        });

        // Create a test device
        \DB::table('devices')->insert([
            'id' => 1,
            'name' => 'Test Device',
            'status' => 'ONLINE',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('monitorings');
        Schema::dropIfExists('devices');
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_observer_updates_current_date_on_create()
    {
        $monitoring = Monitoring::create([
            'date' => now(),
            'device_id' => 1,
            'boe' => 'TEST123',
            'vehicle_number' => 'VH123',
            'regime' => 'TEST',
            'destination' => 'TEST DEST'
        ]);

        $monitoring->refresh();
        $this->assertNotNull($monitoring->current_date);
        $this->assertEquals(
            now()->format('Y-m-d H:i:00'),
            $monitoring->current_date->format('Y-m-d H:i:00')
        );
    }

    public function test_observer_updates_current_date_on_retrieve()
    {
        // Create record with old date
        $monitoring = Monitoring::create([
            'date' => now(),
            'device_id' => 1,
            'boe' => 'TEST123',
            'vehicle_number' => 'VH123',
            'regime' => 'TEST',
            'destination' => 'TEST DEST',
            'current_date' => now()->subDay()
        ]);

        // Clear events from creation
        $monitoring->unsetEventDispatcher();

        // Simulate some time passing
        Carbon::setTestNow(now()->addHour());

        // Retrieve the record
        $retrieved = Monitoring::find($monitoring->id);
        $retrieved->refresh();

        $this->assertNotEquals(
            $monitoring->current_date->format('Y-m-d H:i:00'),
            $retrieved->current_date->format('Y-m-d H:i:00')
        );
        $this->assertEquals(
            now()->format('Y-m-d H:i:00'),
            $retrieved->current_date->format('Y-m-d H:i:00')
        );
    }

    public function test_observer_updates_current_date_on_update()
    {
        $monitoring = Monitoring::create([
            'date' => now(),
            'device_id' => 1,
            'boe' => 'TEST123',
            'vehicle_number' => 'VH123',
            'regime' => 'TEST',
            'destination' => 'TEST DEST'
        ]);

        $monitoring->refresh();
        $originalDate = $monitoring->current_date;

        // Simulate some time passing
        Carbon::setTestNow(now()->addHour());

        $monitoring->update(['boe' => 'UPDATED123']);
        $monitoring->refresh();

        $this->assertNotEquals(
            $originalDate->format('Y-m-d H:i:00'),
            $monitoring->current_date->format('Y-m-d H:i:00')
        );
        $this->assertEquals(
            now()->format('Y-m-d H:i:00'),
            $monitoring->current_date->format('Y-m-d H:i:00')
        );
    }

    public function test_command_updates_current_date()
    {
        // Create test records
        Monitoring::factory()->count(3)->create([
            'current_date' => null
        ]);

        // Run the command
        $this->artisan('update:monitoring-current-date')
            ->assertSuccessful();

        // Assert all records were updated
        $this->assertDatabaseCount('monitorings', 3);
        $this->assertDatabaseHas('monitorings', [
            'current_date' => now()
        ]);

        // Check if all records have the current_date set
        $allUpdated = Monitoring::whereNotNull('current_date')->count() === 3;
        $this->assertTrue($allUpdated, 'Not all records were updated with current_date');
    }

    public function test_command_updates_existing_dates()
    {
        // Create test records with existing current_date
        $oldDate = now()->subMonth();
        Monitoring::factory()->count(3)->create([
            'current_date' => $oldDate
        ]);

        // Run the command
        $this->artisan('update:monitoring-current-date')
            ->assertSuccessful();

        // Verify all records were updated
        $this->assertEquals(3, Monitoring::where('current_date', '>', $oldDate)->count());
        $this->assertEquals(0, Monitoring::where('current_date', $oldDate)->count());
    }
}
