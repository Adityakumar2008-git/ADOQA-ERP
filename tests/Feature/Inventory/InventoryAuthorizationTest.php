<?php

namespace Tests\Feature\Inventory;

use App\Actions\Procurement\CreateSupplier;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected Business $otherBusiness;

    protected User $owner;

    protected User $staff;

    protected User $platformAdmin;

    protected Warehouse $warehouse;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create();
        $this->otherBusiness = Business::factory()->create();

        $this->owner = User::factory()->create(['status' => UserStatus::Active]);
        $this->staff = User::factory()->create(['status' => UserStatus::Active]);
        $this->platformAdmin = User::factory()->create(['status' => UserStatus::Active, 'is_platform_admin' => true]);

        // Owner membership & role
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

        // Staff membership & role (no adjust, no purchase approval, no payment posting)
        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->staff->id,
            'is_owner' => false,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        $staffRole = Role::where('slug', 'staff')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->staff->id,
            'role_id' => $staffRole->id,
            'business_id' => $this->business->id,
        ]);

        // Platform Admin role (platform-scoped, no business membership)
        $adminRole = Role::where('slug', 'platform_admin')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->platformAdmin->id,
            'role_id' => $adminRole->id,
            'business_id' => null,
        ]);

        $this->warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Main Warehouse',
            'code' => 'MAIN-01',
            'status' => 'active',
            'is_default' => true,
        ]);

        $supplierAction = app(CreateSupplier::class);
        $this->supplier = $supplierAction->execute($this->owner, $this->business, [
            'name' => 'Authorized Vendor',
            'code' => 'AUTH-VEND',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('owner.inventory.stock.index'));
        $response->assertRedirect('/login');

        $response = $this->get(route('owner.purchasing.suppliers.index'));
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_staff_cannot_create_warehouse_or_adjust_stock(): void
    {
        // Attempt to create warehouse
        $whResponse = $this->actingAs($this->staff)
            ->post(route('owner.inventory.warehouses.store'), [
                'name' => 'Illegal Depot',
            ]);
        $whResponse->assertForbidden();

        // Attempt to adjust stock
        $adjResponse = $this->actingAs($this->staff)
            ->get(route('owner.inventory.adjustments.create'));
        $adjResponse->assertForbidden();
    }

    public function test_unauthorized_staff_cannot_post_supplier_payment(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('owner.purchasing.payments.store'), [
                'supplier_id' => $this->supplier->id,
                'amount' => 100,
                'payment_mode' => 'cash',
            ]);

        $response->assertForbidden();
    }

    public function test_platform_admin_without_business_membership_denied_tenant_inventory_access(): void
    {
        $response = $this->actingAs($this->platformAdmin)
            ->get(route('owner.inventory.stock.index'));

        // Without membership, tenant resolution aborts with 403 or redirect
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    public function test_owner_granted_access_to_all_inventory_and_purchasing_surfaces(): void
    {
        $routes = [
            route('owner.inventory.stock.index'),
            route('owner.inventory.stock.ledger'),
            route('owner.inventory.warehouses.index'),
            route('owner.inventory.adjustments.index'),
            route('owner.inventory.transfers.index'),
            route('owner.inventory.stock.reconcile'),
            route('owner.purchasing.suppliers.index'),
            route('owner.purchasing.orders.index'),
            route('owner.purchasing.bills.index'),
            route('owner.purchasing.receipts.index'),
            route('owner.purchasing.payments.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->owner)->get($url);
            $response->assertOk();
        }
    }
}
