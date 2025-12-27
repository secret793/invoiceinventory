<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AllocationPoint;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AllocationPointPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure tables are cleared
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::truncate();
        Permission::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Create roles
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        Role::create(['name' => 'Regular User', 'guard_name' => 'web']);
    }

    /** @test */
    public function it_creates_permission_when_allocation_point_is_created()
    {
        $point = AllocationPoint::create([
            'name' => 'Kamkam Point',
            'location' => 'Kamkam'
        ]);

        $permissionName = 'view_allocationpoint_' . Str::slug($point->name);
        $this->assertDatabaseHas('permissions', [
            'name' => $permissionName,
            'guard_name' => 'web'
        ]);
    }

    /** @test */
    public function it_updates_permission_when_allocation_point_name_is_updated()
    {
        $point = AllocationPoint::create([
            'name' => 'Kamkam Point',
            'location' => 'Kamkam'
        ]);

        $oldPermissionName = 'view_allocationpoint_' . Str::slug($point->name);
        
        $point->update(['name' => 'New Kamkam Point']);
        
        $newPermissionName = 'view_allocationpoint_' . Str::slug($point->name);
        
        $this->assertDatabaseMissing('permissions', [
            'name' => $oldPermissionName,
            'guard_name' => 'web'
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => $newPermissionName,
            'guard_name' => 'web'
        ]);
    }

    /** @test */
    public function it_deletes_permission_when_allocation_point_is_deleted()
    {
        $point = AllocationPoint::create([
            'name' => 'Kamkam Point',
            'location' => 'Kamkam'
        ]);

        $permissionName = 'view_allocationpoint_' . Str::slug($point->name);
        
        $point->delete();
        
        $this->assertDatabaseMissing('permissions', [
            'name' => $permissionName,
            'guard_name' => 'web'
        ]);
    }

    /** @test */
    public function users_can_only_access_permitted_allocation_points()
    {
        // Create allocation points
        $kamkam = AllocationPoint::create([
            'name' => 'Kamkam Point',
            'location' => 'Kamkam'
        ]);
        
        $aflao = AllocationPoint::create([
            'name' => 'Aflao Point',
            'location' => 'Aflao'
        ]);

        // Create users with password
        $userA = User::factory()->create(['password' => bcrypt('password')]);
        $userB = User::factory()->create(['password' => bcrypt('password')]);
        $superAdmin = User::factory()->create(['password' => bcrypt('password')]);

        // Assign roles
        $regularRole = Role::where('name', 'Regular User')->first();
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        
        $userA->assignRole($regularRole);
        $userB->assignRole($regularRole);
        $superAdmin->assignRole($superAdminRole);

        // Give specific permissions
        Permission::create(['name' => 'view_allocationpoint_' . Str::slug($kamkam->name), 'guard_name' => 'web']);
        Permission::create(['name' => 'view_allocationpoint_' . Str::slug($aflao->name), 'guard_name' => 'web']);
        
        $userA->givePermissionTo('view_allocationpoint_' . Str::slug($kamkam->name));
        $userB->givePermissionTo('view_allocationpoint_' . Str::slug($aflao->name));

        // Test User A access
        $this->actingAs($userA);
        $userAPoints = AllocationPoint::all();
        $this->assertTrue($userAPoints->contains($kamkam));
        $this->assertFalse($userAPoints->contains($aflao));

        // Test User B access
        $this->actingAs($userB);
        $userBPoints = AllocationPoint::all();
        $this->assertFalse($userBPoints->contains($kamkam));
        $this->assertTrue($userBPoints->contains($aflao));

        // Test Super Admin access
        $this->actingAs($superAdmin);
        $adminPoints = AllocationPoint::all();
        $this->assertTrue($adminPoints->contains($kamkam));
        $this->assertTrue($adminPoints->contains($aflao));
    }
}
