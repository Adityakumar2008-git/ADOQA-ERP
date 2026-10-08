<?php

namespace Tests\Feature\Catalogue;

use App\Enums\CatalogStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogueTenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $businessA;

    protected User $ownerA;

    protected Unit $unitA;

    protected Business $businessB;

    protected User $ownerB;

    protected Unit $unitB;

    protected Category $categoryB;

    protected Brand $brandB;

    protected Item $itemB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();

        // 1. Business A setup
        $this->businessA = Business::factory()->create(['name' => 'Business Alpha']);
        $this->ownerA = User::factory()->create(['status' => UserStatus::Active]);
        BusinessMember::create([
            'business_id' => $this->businessA->id,
            'user_id' => $this->ownerA->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        UserRole::create([
            'user_id' => $this->ownerA->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->businessA->id,
        ]);
        $this->unitA = Unit::factory()->create([
            'business_id' => $this->businessA->id,
            'code' => 'PCS',
            'status' => CatalogStatus::Active,
        ]);

        // 2. Business B setup
        $this->businessB = Business::factory()->create(['name' => 'Business Beta']);
        $this->ownerB = User::factory()->create(['status' => UserStatus::Active]);
        BusinessMember::create([
            'business_id' => $this->businessB->id,
            'user_id' => $this->ownerB->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        UserRole::create([
            'user_id' => $this->ownerB->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->businessB->id,
        ]);
        $this->unitB = Unit::factory()->create([
            'business_id' => $this->businessB->id,
            'code' => 'PCS',
            'status' => CatalogStatus::Active,
        ]);
        $this->categoryB = Category::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Secret Category B',
            'status' => CatalogStatus::Active,
        ]);
        $this->brandB = Brand::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Secret Brand B',
            'status' => CatalogStatus::Active,
        ]);
        $this->itemB = Item::factory()->create([
            'business_id' => $this->businessB->id,
            'name' => 'Secret Item B',
            'unit_id' => $this->unitB->id,
            'sale_price' => '999.00',
        ]);
    }

    public function test_business_a_cannot_view_business_b_item(): void
    {
        $response = $this->actingAs($this->ownerA)->get("/owner/catalogue/items/{$this->itemB->id}");
        $response->assertNotFound();
    }

    public function test_business_a_cannot_edit_business_b_item(): void
    {
        $response = $this->actingAs($this->ownerA)->get("/owner/catalogue/items/{$this->itemB->id}/edit");
        $response->assertNotFound();
    }

    public function test_business_a_cannot_update_business_b_item(): void
    {
        $payload = [
            'name' => 'Maliciously Hijacked Item',
            'unit_id' => $this->unitA->id,
            'inventory_mode' => 'normal',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ];

        $response = $this->actingAs($this->ownerA)->put("/owner/catalogue/items/{$this->itemB->id}", $payload);
        $response->assertNotFound();

        $this->assertDatabaseMissing('items', [
            'id' => $this->itemB->id,
            'name' => 'Maliciously Hijacked Item',
        ]);
    }

    public function test_business_a_cannot_toggle_status_of_business_b_item(): void
    {
        $response = $this->actingAs($this->ownerA)->patch("/owner/catalogue/items/{$this->itemB->id}/status");
        $response->assertNotFound();
    }

    public function test_business_a_cannot_delete_business_b_item(): void
    {
        $response = $this->actingAs($this->ownerA)->delete("/owner/catalogue/items/{$this->itemB->id}");
        $response->assertNotFound();

        $this->assertDatabaseHas('items', ['id' => $this->itemB->id]);
    }

    public function test_business_a_cannot_assign_business_b_category(): void
    {
        $payload = [
            'name' => 'Item Attempting Cross Tenant Category',
            'category_id' => $this->categoryB->id,
            'unit_id' => $this->unitA->id,
            'inventory_mode' => 'normal',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ];

        $response = $this->actingAs($this->ownerA)->post('/owner/catalogue/items', $payload);
        $response->assertSessionHasErrors('category_id');
    }

    public function test_business_a_cannot_assign_business_b_brand(): void
    {
        $payload = [
            'name' => 'Item Attempting Cross Tenant Brand',
            'brand_id' => $this->brandB->id,
            'unit_id' => $this->unitA->id,
            'inventory_mode' => 'normal',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ];

        $response = $this->actingAs($this->ownerA)->post('/owner/catalogue/items', $payload);
        $response->assertSessionHasErrors('brand_id');
    }

    public function test_business_a_cannot_assign_business_b_unit(): void
    {
        $payload = [
            'name' => 'Item Attempting Cross Tenant Unit',
            'unit_id' => $this->unitB->id,
            'inventory_mode' => 'normal',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ];

        $response = $this->actingAs($this->ownerA)->post('/owner/catalogue/items', $payload);
        $response->assertSessionHasErrors('unit_id');
    }

    public function test_client_supplied_business_id_is_strictly_ignored(): void
    {
        $payload = [
            'business_id' => $this->businessB->id, // Malicious spoofing attempt
            'name' => 'Tenant Spoof Item',
            'unit_id' => $this->unitA->id,
            'inventory_mode' => 'normal',
            'cost_price' => '50.00',
            'sale_price' => '75.00',
        ];

        $response = $this->actingAs($this->ownerA)->post('/owner/catalogue/items?tenant='.$this->businessB->id, $payload);
        $response->assertRedirect();

        // The item is strictly created in Business A, NEVER Business B
        $this->assertDatabaseHas('items', [
            'business_id' => $this->businessA->id,
            'name' => 'Tenant Spoof Item',
        ]);
        $this->assertDatabaseMissing('items', [
            'business_id' => $this->businessB->id,
            'name' => 'Tenant Spoof Item',
        ]);
    }
}
