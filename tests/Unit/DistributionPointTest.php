<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\DistributionPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DistributionPointTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_counts_received_devices_correctly()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // Create 3 devices with RECEIVED status
        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        // Create 2 devices with other statuses
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);

        $this->assertEquals(3, $point->getReceivedDevicesCount());
    }

    /** @test */
    public function it_counts_other_status_devices_correctly()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // Create 2 devices with RECEIVED status
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        // Create 4 devices with other statuses
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);

        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'OFFLINE'
        ]);

        $this->assertEquals(4, $point->getOtherStatusDevicesCount());
    }

    /** @test */
    public function it_caches_device_counts()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // Create some devices
        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        // First call should cache the result
        $count = DistributionPoint::getCachedReceivedCount($point->id);
        $this->assertEquals(3, $count);

        // Add more devices (which won't affect the cached count yet)
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        // Should still return cached value
        $cachedCount = DistributionPoint::getCachedReceivedCount($point->id);
        $this->assertEquals(3, $cachedCount);

        // Clear cache
        $point->clearDeviceCountCache();

        // Should now return updated count
        $updatedCount = DistributionPoint::getCachedReceivedCount($point->id);
        $this->assertEquals(5, $updatedCount);
    }

    /** @test */
    public function it_returns_empty_string_for_zero_counts()
    {
        // Create a distribution point with no devices
        $point = DistributionPoint::factory()->create();

        $this->assertEquals('', DistributionPoint::getReceivedBadgeForPoint($point->id));
        $this->assertEquals('', DistributionPoint::getOtherStatusBadgeForPoint($point->id));

        // Add a device
        Device::factory()->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        // Clear cache to get fresh count
        $point->clearDeviceCountCache();

        $this->assertEquals('1', DistributionPoint::getReceivedBadgeForPoint($point->id));
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Test with non-existent distribution point ID
        $nonExistentId = 9999;

        // Should return 0 instead of throwing an error
        $this->assertEquals(0, DistributionPoint::getCachedReceivedCount($nonExistentId));
        $this->assertEquals(0, DistributionPoint::getCachedOtherStatusCount($nonExistentId));

        // Badge helpers should return empty string
        $this->assertEquals('', DistributionPoint::getReceivedBadgeForPoint($nonExistentId));
        $this->assertEquals('', DistributionPoint::getOtherStatusBadgeForPoint($nonExistentId));
    }

    /** @test */
    public function it_returns_correct_badge_configuration()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // No devices
        $emptyConfig = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('', $emptyConfig['text']);
        $this->assertEquals('secondary', $emptyConfig['color']);

        // Add 2 received devices
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        // Clear cache to get fresh count
        $point->clearDeviceCountCache();

        $receivedConfig = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('2/0', $receivedConfig['text']);
        $this->assertEquals('warning', $receivedConfig['color']); // Yellow/amber for received only

        // Add 3 other status devices
        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);

        // Clear cache to get fresh count
        $point->clearDeviceCountCache();

        $activeConfig = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('2/3', $activeConfig['text']);
        $this->assertEquals('success', $activeConfig['color']); // Green for active devices
    }

    /** @test */
    public function it_handles_badge_helper_methods_correctly()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // Add devices with different statuses
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);

        // Clear cache to get fresh count
        $point->clearDeviceCountCache();

        // Test individual helper methods
        $this->assertEquals('2/3', DistributionPoint::getBadgeText($point->id));
        $this->assertEquals('success', DistributionPoint::getBadgeColor($point->id));
    }

    /** @test */
    public function it_returns_detailed_badge_colors_based_on_ratios()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // No devices
        $this->assertEquals('secondary', DistributionPoint::getDetailedBadgeColor($point->id));

        // Only received devices (4)
        Device::factory()->count(4)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);
        $point->clearDeviceCountCache();
        $this->assertEquals('warning', DistributionPoint::getDetailedBadgeColor($point->id));

        // Only active devices (clear previous and add 3 active)
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);
        $point->clearDeviceCountCache();
        $this->assertEquals('success', DistributionPoint::getDetailedBadgeColor($point->id));

        // Mixed cases with different ratios

        // Mostly active (80% active)
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);
        Device::factory()->count(8)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);
        $point->clearDeviceCountCache();
        $this->assertEquals('success', DistributionPoint::getDetailedBadgeColor($point->id));

        // Mixed but more active (60% active)
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(4)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);
        Device::factory()->count(6)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);
        $point->clearDeviceCountCache();
        $this->assertEquals('info', DistributionPoint::getDetailedBadgeColor($point->id));

        // Mixed but more received (70% received)
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(7)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);
        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);
        $point->clearDeviceCountCache();
        $this->assertEquals('warning', DistributionPoint::getDetailedBadgeColor($point->id));

        // Mostly received (90% received)
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(9)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);
        Device::factory()->count(1)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);
        $point->clearDeviceCountCache();
        $this->assertEquals('danger', DistributionPoint::getDetailedBadgeColor($point->id));
    }

    /** @test */
    public function it_returns_correct_dual_color_badge_configuration()
    {
        // Create a distribution point
        $point = DistributionPoint::factory()->create();

        // No devices
        $emptyConfig = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('', $emptyConfig['text']);
        $this->assertEquals(0, $emptyConfig['receivedRatio']);
        $this->assertEquals(0, $emptyConfig['otherRatio']);

        // Add 2 received devices and 3 other status devices
        Device::factory()->count(2)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        Device::factory()->count(3)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);

        // Clear cache to get fresh count
        $point->clearDeviceCountCache();

        $config = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('2/3', $config['text']);
        $this->assertEquals('danger', $config['receivedColor']);
        $this->assertEquals('warning', $config['otherColor']);
        $this->assertEquals(0.4, $config['receivedRatio']);
        $this->assertEquals(0.6, $config['otherRatio']);

        // Test with only received devices
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(5)->create([
            'distribution_point_id' => $point->id,
            'status' => 'RECEIVED'
        ]);

        $point->clearDeviceCountCache();

        $receivedOnlyConfig = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('5/0', $receivedOnlyConfig['text']);
        $this->assertEquals(1.0, $receivedOnlyConfig['receivedRatio']);
        $this->assertEquals(0.0, $receivedOnlyConfig['otherRatio']);

        // Test with only other status devices
        Device::where('distribution_point_id', $point->id)->delete();
        Device::factory()->count(4)->create([
            'distribution_point_id' => $point->id,
            'status' => 'ONLINE'
        ]);

        $point->clearDeviceCountCache();

        $otherOnlyConfig = DistributionPoint::getBadgeConfig($point->id);
        $this->assertEquals('0/4', $otherOnlyConfig['text']);
        $this->assertEquals(0.0, $otherOnlyConfig['receivedRatio']);
        $this->assertEquals(1.0, $otherOnlyConfig['otherRatio']);
    }
}



