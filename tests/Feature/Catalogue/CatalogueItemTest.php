<?php

namespace Tests\Feature\Catalogue;

use App\Enums\CatalogStatus;
use App\Enums\InventoryMode;
use App\Enums\ItemStatus;
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

class CatalogueItemTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected Unit $unit;

    protected Category $category;

    protected Brand $brand;

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

        $this->unit = Unit::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Piece',
            'code' => 'PCS',
            'status' => CatalogStatus::Active,
        ]);

        $this->category = Category::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Smartphones',
            'status' => CatalogStatus::Active,
        ]);

        $this->brand = Brand::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Apple',
            'status' => CatalogStatus::Active,
        ]);
    }

    public function test_owner_can_view_catalogue_items_index(): void
    {
        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'iPhone 16 Pro',
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->actingAs($this->owner)->get('/owner/catalogue/items');

        $response->assertOk();
        $response->assertViewIs('owner.catalogue.items.index');
        $response->assertSee('iPhone 16 Pro');
    }

    public function test_owner_can_create_catalogue_item_with_full_valid_metadata(): void
    {
        $payload = [
            'name' => 'MacBook Air M3',
            'description' => '13-inch liquid retina display',
            'sku' => 'MBA-M3-13',
            'barcode' => '195949123456',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Serial->value,
            'cost_price' => '85000.00',
            'sale_price' => '99900.00',
            'mrp' => '114900.00',
            'tax_rate' => '18.00',
            'minimum_stock_threshold' => '5.00',
            'status' => ItemStatus::Active->value,
        ];

        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('items', [
            'business_id' => $this->business->id,
            'name' => 'MacBook Air M3',
            'sku' => 'MBA-M3-13',
            'barcode' => '195949123456',
            'inventory_mode' => 'serial',
            'cost_price' => '85000.00',
            'sale_price' => '99900.00',
            'mrp' => '114900.00',
            'tax_rate' => '18.00',
            'status' => 'active',
        ]);

        $item = Item::where('sku', 'MBA-M3-13')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'action' => 'item.created',
            'auditable_type' => Item::class,
            'auditable_id' => $item->id,
        ]);
    }

    public function test_owner_can_update_existing_item(): void
    {
        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'AirPods Pro 2',
            'sku' => 'APP-2-GEN',
            'unit_id' => $this->unit->id,
            'sale_price' => '24900.00',
            'cost_price' => '19000.00',
        ]);

        $payload = [
            'name' => 'AirPods Pro 2 (USB-C)',
            'sku' => 'APP-2-USBC',
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Serial->value,
            'cost_price' => '19500.00',
            'sale_price' => '25900.00',
            'mrp' => '26900.00',
            'tax_rate' => '18.00',
            'minimum_stock_threshold' => '10.00',
            'status' => ItemStatus::Active->value,
        ];

        $response = $this->actingAs($this->owner)->put("/owner/catalogue/items/{$item->id}", $payload);

        $response->assertRedirect("/owner/catalogue/items/{$item->id}");

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'AirPods Pro 2 (USB-C)',
            'sku' => 'APP-2-USBC',
            'sale_price' => '25900.00',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'action' => 'item.updated',
            'auditable_id' => $item->id,
        ]);
    }

    public function test_owner_can_activate_and_deactivate_item(): void
    {
        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'status' => ItemStatus::Active,
        ]);

        // 1. Deactivate
        $response = $this->actingAs($this->owner)->patch("/owner/catalogue/items/{$item->id}/status");
        $response->assertRedirect();
        $this->assertEquals(ItemStatus::Inactive, $item->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $this->business->id,
            'action' => 'item.deactivated',
            'auditable_id' => $item->id,
        ]);

        // 2. Reactivate
        $response = $this->actingAs($this->owner)->patch("/owner/catalogue/items/{$item->id}/status");
        $response->assertRedirect();
        $this->assertEquals(ItemStatus::Active, $item->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $this->business->id,
            'action' => 'item.activated',
            'auditable_id' => $item->id,
        ]);
    }

    public function test_duplicate_sku_in_same_business_is_rejected(): void
    {
        Item::factory()->create([
            'business_id' => $this->business->id,
            'sku' => 'SKU-DUPLICATE-01',
            'unit_id' => $this->unit->id,
        ]);

        $payload = [
            'name' => 'Conflicting SKU Product',
            'sku' => 'SKU-DUPLICATE-01',
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal->value,
            'cost_price' => '100.00',
            'sale_price' => '150.00',
        ];

        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', $payload);

        $response->assertSessionHasErrors('sku');
    }

    public function test_duplicate_barcode_in_same_business_is_rejected(): void
    {
        Item::factory()->create([
            'business_id' => $this->business->id,
            'barcode' => '8901234567890',
            'unit_id' => $this->unit->id,
        ]);

        $payload = [
            'name' => 'Conflicting Barcode Product',
            'barcode' => '8901234567890',
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal->value,
            'cost_price' => '100.00',
            'sale_price' => '150.00',
        ];

        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', $payload);

        $response->assertSessionHasErrors('barcode');
    }

    public function test_assigning_inactive_category_to_new_item_is_rejected(): void
    {
        $inactiveCategory = Category::factory()->create([
            'business_id' => $this->business->id,
            'status' => CatalogStatus::Inactive,
        ]);

        $payload = [
            'name' => 'Item With Inactive Category',
            'category_id' => $inactiveCategory->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal->value,
            'cost_price' => '100.00',
            'sale_price' => '150.00',
        ];

        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', $payload);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_assigning_inactive_brand_to_new_item_is_rejected(): void
    {
        $inactiveBrand = Brand::factory()->create([
            'business_id' => $this->business->id,
            'status' => CatalogStatus::Inactive,
        ]);

        $payload = [
            'name' => 'Item With Inactive Brand',
            'brand_id' => $inactiveBrand->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal->value,
            'cost_price' => '100.00',
            'sale_price' => '150.00',
        ];

        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', $payload);

        $response->assertSessionHasErrors('brand_id');
    }

    public function test_assigning_inactive_unit_to_new_item_is_rejected(): void
    {
        $inactiveUnit = Unit::factory()->create([
            'business_id' => $this->business->id,
            'status' => CatalogStatus::Inactive,
        ]);

        $payload = [
            'name' => 'Item With Inactive Unit',
            'unit_id' => $inactiveUnit->id,
            'inventory_mode' => InventoryMode::Normal->value,
            'cost_price' => '100.00',
            'sale_price' => '150.00',
        ];

        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', $payload);

        $response->assertSessionHasErrors('unit_id');
    }

    public function test_negative_prices_and_invalid_mrp_boundaries_are_rejected(): void
    {
        // 1. Negative cost
        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', [
            'name' => 'Invalid Cost',
            'unit_id' => $this->unit->id,
            'inventory_mode' => 'normal',
            'cost_price' => '-10.00',
            'sale_price' => '50.00',
        ]);
        $response->assertSessionHasErrors('cost_price');

        // 2. Negative sale price
        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', [
            'name' => 'Invalid Sale',
            'unit_id' => $this->unit->id,
            'inventory_mode' => 'normal',
            'cost_price' => '10.00',
            'sale_price' => '-50.00',
        ]);
        $response->assertSessionHasErrors('sale_price');

        // 3. MRP lower than sale price
        $response = $this->actingAs($this->owner)->post('/owner/catalogue/items', [
            'name' => 'MRP Under Sale',
            'unit_id' => $this->unit->id,
            'inventory_mode' => 'normal',
            'cost_price' => '100.00',
            'sale_price' => '150.00',
            'mrp' => '120.00', // MRP lower than sale price!
        ]);
        $response->assertSessionHasErrors('mrp');
    }
}
