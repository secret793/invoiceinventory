<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\DeviceRetrieval;
use App\Models\Destination;
use App\Models\Regime;
use App\Observers\DestinationObserver;

class DestinationObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register the observer
        Destination::observe(DestinationObserver::class);
    }

    /** @test */
    public function it_updates_device_retrieval_destination_based_on_regime()
    {
        // Create a regime
        $regime = Regime::create(['name' => 'warehouse', 'description' => 'Warehouse Regime', 'is_active' => true]);

        // Create a destination
        $destination = Destination::create(['name' => 'Ghana', 'regime_id' => $regime->id, 'address' => 'Accra', 'latitude' => 5.6037, 'longitude' => -0.1870, 'default_location' => true, 'status' => 'active']);

        // Create a device retrieval with the regime
        $deviceRetrieval = DeviceRetrieval::create([
            'date' => now(),
            'device_id' => 1,
            'regime' => $regime->id,
            'destination' => '',
            'route_id' => 1,
            'long_route_id' => null,
            'manifest_date' => now(),
            'status' => 'pending'
        ]);

        // Trigger the retrieved event
        $destination->refresh();

        // Assert that the destination was updated based on the regime
        $this->assertEquals('Ghana', $deviceRetrieval->fresh()->destination);
    }
}
