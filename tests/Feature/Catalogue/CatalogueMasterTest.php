<?php

namespace Tests\Feature\Catalogue;

use App\Actions\Catalogue\SeedDefaultBusinessUnits;
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

class CatalogueMasterTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

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
    }

    public function test_canonical_units_can_be_seeded_idempotently(): void
    {
        $action = new SeedDefaultBusinessUnits;
        $action->execute($this->business);

        $this->assertDatabaseHas('units', [
            'business_id' => $this->business->id,
            'code' => 'PCS',
            'name' => 'Piece',
        ]);
        $this->assertDatabaseHas('units', [
            'business_id' => $this->business->id,
            'code' => 'KG',
            'name' => 'Kilogram',
        ]);
        $this->assertDatabaseHas('units', [
            'business_id' => $this->business->id,
            'code' => 'BAG',
            'name' => 'Bag',
        ]);

        // Second run must be idempotent without duplication
        $action->execute($this->business);
        $count = Unit::where('business_id', $this->business->id)->where('code', 'PCS')->count();
        $this->assertEquals(1, $count);
    }

    public function test_owner_can_crud_category(): void
    {
        // 1. Create
        $response = $this->actingAs($this->owner)->post('/owner/catalogue/categories', [
            'name' => 'Industrial Hardware',
            'description' => 'Heavy nuts and bolts',
        ]);
        $response->assertRedirect('/owner/catalogue/categories');

        $this->assertDatabaseHas('categories', [
            'business_id' => $this->business->id,
            'name' => 'Industrial Hardware',
            'slug' => 'industrial-hardware',
        ]);

        $category = Category::where('business_id', $this->business->id)->firstOrFail();

        // 2. Update
        $response = $this->actingAs($this->owner)->put("/owner/catalogue/categories/{$category->id}", [
            'name' => 'Industrial Tools & Hardware',
            'slug' => 'industrial-tools-hardware',
            'description' => 'Updated description',
        ]);
        $response->assertRedirect('/owner/catalogue/categories');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Industrial Tools & Hardware',
            'slug' => 'industrial-tools-hardware',
        ]);

        // 3. Toggle Status
        $response = $this->actingAs($this->owner)->patch("/owner/catalogue/categories/{$category->id}/status");
        $response->assertRedirect();
        $this->assertEquals(CatalogStatus::Inactive, $category->fresh()->status);

        // 4. Delete
        $response = $this->actingAs($this->owner)->delete("/owner/catalogue/categories/{$category->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_referenced_by_item_cannot_be_deleted(): void
    {
        $category = Category::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);

        Item::factory()->create([
            'business_id' => $this->business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
        ]);

        $response = $this->actingAs($this->owner)->delete("/owner/catalogue/categories/{$category->id}");
        $response->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_owner_can_crud_brand(): void
    {
        // 1. Create
        $response = $this->actingAs($this->owner)->post('/owner/catalogue/brands', [
            'name' => 'Sony Corporation',
            'description' => 'Audio and video equipment',
        ]);
        $response->assertRedirect('/owner/catalogue/brands');

        $this->assertDatabaseHas('brands', [
            'business_id' => $this->business->id,
            'name' => 'Sony Corporation',
            'slug' => 'sony-corporation',
        ]);

        $brand = Brand::where('business_id', $this->business->id)->firstOrFail();

        // 2. Update
        $response = $this->actingAs($this->owner)->put("/owner/catalogue/brands/{$brand->id}", [
            'name' => 'Sony Electronics',
            'slug' => 'sony-electronics',
        ]);
        $response->assertRedirect('/owner/catalogue/brands');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Sony Electronics',
        ]);

        // 3. Delete
        $response = $this->actingAs($this->owner)->delete("/owner/catalogue/brands/{$brand->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_brand_referenced_by_item_cannot_be_deleted(): void
    {
        $brand = Brand::factory()->create(['business_id' => $this->business->id]);
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);

        Item::factory()->create([
            'business_id' => $this->business->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
        ]);

        $response = $this->actingAs($this->owner)->delete("/owner/catalogue/brands/{$brand->id}");
        $response->assertSessionHasErrors('brand');

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_owner_can_crud_unit(): void
    {
        // 1. Create
        $response = $this->actingAs($this->owner)->post('/owner/catalogue/units', [
            'name' => 'Pack of 10',
            'code' => 'PK10',
        ]);
        $response->assertRedirect('/owner/catalogue/units');

        $this->assertDatabaseHas('units', [
            'business_id' => $this->business->id,
            'name' => 'Pack of 10',
            'code' => 'PK10',
        ]);

        $unit = Unit::where('business_id', $this->business->id)->where('code', 'PK10')->firstOrFail();

        // 2. Toggle Status
        $response = $this->actingAs($this->owner)->patch("/owner/catalogue/units/{$unit->id}/status");
        $response->assertRedirect();
        $this->assertEquals(CatalogStatus::Inactive, $unit->fresh()->status);

        // 3. Delete
        $response = $this->actingAs($this->owner)->delete("/owner/catalogue/units/{$unit->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_unit_referenced_by_item_cannot_be_deleted(): void
    {
        $unit = Unit::factory()->create(['business_id' => $this->business->id]);

        Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
        ]);

        $response = $this->actingAs($this->owner)->delete("/owner/catalogue/units/{$unit->id}");
        $response->assertSessionHasErrors('unit');

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }
}
