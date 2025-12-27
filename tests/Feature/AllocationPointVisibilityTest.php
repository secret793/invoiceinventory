<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AllocationPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationPointVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function allocation_officer_can_see_allocation_points_submenus()
    {
        // Create roles and permissions
        $allocationOfficerRole = Role::create(['name' => 'Allocation Officer']);
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        
        // Create allocation points
        $allocationPoint1 = AllocationPoint::create(['name' => 'Soma', 'location' => 'Location 1']);
        $allocationPoint2 = AllocationPoint::create(['name' => 'Farafeni', 'location' => 'Location 2']);

        // Create a user and assign the Allocation Officer role
        $user = User::factory()->create();
        $user->assignRole($allocationOfficerRole);

        // Simulate logging in as the Allocation Officer
        $this->actingAs($user);

        // Check if the allocation points are visible in the navigation
        $response = $this->get('/admin'); // Adjust the URL to your admin panel route

        // Assert that the response contains the allocation points
        $response->assertSee('Allocation Points'); // Check if the main menu is visible
        $response->assertSee('Soma'); // Check if the submenu for Soma is visible
        $response->assertSee('Farafeni'); // Check if the submenu for Farafeni is visible
    }
} 