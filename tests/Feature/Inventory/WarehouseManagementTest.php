<?php

namespace Tests\Feature\Inventory;

use App\Actions\Business\CreateBusiness;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Enums\WarehouseStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected User $otherOwner;

    protected Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create();
        $this->owner = User::factory()->create(['status' => UserStatus::Active]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->business->id,
        ]);

        // Other business for cross-tenant testing
        $this->otherBusiness = Business::factory()->create();
        $this->otherOwner = User::factory()->create(['status' => UserStatus::Active]);
        BusinessMember::create([
            'business_id' => $this->otherBusiness->id,
            'user_id' => $this->otherOwner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        UserRole::create([
            'user_id' => $this->otherOwner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->otherBusiness->id,
        ]);
    }

    public function test_default_warehouse_is_provisioned_on_business_creation(): void
    {
        $createAction = app(CreateBusiness::class);
        $user = User::factory()->create(['is_platform_admin' => true, 'status' => UserStatus::Active]);
        $platformAdminRole = Role::where('slug', 'platform_admin')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $platformAdminRole->id,
            'business_id' => null,
        ]);

        $business = $createAction->execute($user, [
            'name' => 'Auto Provisioned Store',
            'owner_name' => 'Store Owner',
            'owner_email' => 'storeowner_'.uniqid().'@example.com',
            'owner_password' => 'Password123#',
            'currency' => 'INR',
        ]);

        $defaultWarehouse = Warehouse::where('business_id', $business->id)->where('is_default', true)->first();

        $this->assertNotNull($defaultWarehouse);
        $this->assertEquals('Main Warehouse', $defaultWarehouse->name);
        $this->assertEquals(WarehouseStatus::Active, $defaultWarehouse->status);
    }

    public function test_owner_can_create_additional_warehouse(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.inventory.warehouses.store'), [
                'name' => 'North Distribution Hub',
                'code' => 'NORTH-HUB',
                'address' => 'Industrial Area Phase 2',
            ]);

        $response->assertRedirect(route('owner.inventory.warehouses.index'));

        $this->assertDatabaseHas('warehouses', [
            'business_id' => $this->business->id,
            'name' => 'North Distribution Hub',
            'code' => 'NORTH-HUB',
            'is_default' => false,
            'status' => 'active',
        ]);
    }

    public function test_cross_tenant_warehouse_isolation(): void
    {
        $otherWarehouse = Warehouse::create([
            'business_id' => $this->otherBusiness->id,
            'name' => 'Competitor Depot',
            'code' => 'COMP-01',
            'status' => WarehouseStatus::Active,
            'is_default' => false,
        ]);

        // Attempt to edit competitor's warehouse
        $response = $this->actingAs($this->owner)
            ->get(route('owner.inventory.warehouses.edit', $otherWarehouse));

        $response->assertNotFound();

        // Attempt to update competitor's warehouse
        $updateResponse = $this->actingAs($this->owner)
            ->put(route('owner.inventory.warehouses.update', $otherWarehouse), [
                'name' => 'Hacked Name',
                'code' => 'HACKED',
                'status' => 'active',
            ]);

        $updateResponse->assertNotFound();
        $this->assertEquals('Competitor Depot', $otherWarehouse->fresh()->name);
    }

    public function test_cannot_deactivate_warehouse_with_active_stock_balance(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Active Storage',
            'code' => 'ACT-01',
            'status' => WarehouseStatus::Active,
            'is_default' => false,
        ]);

        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
        ]);

        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 25,
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('owner.inventory.warehouses.update', $warehouse), [
                'name' => 'Active Storage',
                'code' => 'ACT-01',
                'status' => 'inactive',
            ]);

        $response->assertSessionHasErrors(['status']);
        $this->assertEquals(WarehouseStatus::Active, $warehouse->fresh()->status);
    }

    public function test_owner_can_create_internal_stock_location(): void
    {
        $warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Central Depot',
            'code' => 'CENT-01',
            'status' => WarehouseStatus::Active,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.inventory.warehouses.locations.store', $warehouse), [
                'name' => 'Aisle 3 Shelf B',
                'code' => 'A3-SB',
            ]);

        $response->assertRedirect(route('owner.inventory.warehouses.edit', $warehouse));

        $this->assertDatabaseHas('stock_locations', [
            'business_id' => $this->business->id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Aisle 3 Shelf B',
            'code' => 'A3-SB',
            'status' => 'active',
        ]);
    }
}
