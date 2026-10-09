<?php

namespace Tests\Feature\Owner;

use App\Actions\Procurement\CreatePurchaseBill;
use App\Actions\Procurement\CreateSupplier;
use App\Actions\Tenancy\SetBusinessRolePermissionOverride;
use App\Actions\Tenancy\UpdateBusinessModuleSettings;
use App\Enums\BusinessModule;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\PermissionOverrideState;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\AuthorizationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DynamicNavigationTest extends TestCase
{
    use DatabaseTransactions;

    protected AuthorizationService $authService;

    protected UpdateBusinessModuleSettings $moduleSettingsAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->authService = app(AuthorizationService::class);
        $this->moduleSettingsAction = app(UpdateBusinessModuleSettings::class);
    }

    protected function createTenantWithUser(string $roleSlug = 'owner'): array
    {
        $business = Business::factory()->create([
            'status' => BusinessStatus::Active,
        ]);

        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'is_platform_admin' => false,
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => ($roleSlug === 'owner'),
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $role = Role::where('slug', $roleSlug)->whereNull('business_id')->firstOrFail();

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        return [$business, $user, $role];
    }

    public function test_catalogue_enabled_displays_sidebar_links_and_dashboard_card(): void
    {
        [$business, $owner] = $this->createTenantWithUser('owner');

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertSee('Commerce');
        $response->assertSee('Items Catalogue');
        $response->assertSee('Categories');
        $response->assertSee('Brands');
        $response->assertSee('Units');
        $response->assertSee('Product Catalogue');
        $response->assertSee(route('owner.catalogue.items.index'));
    }

    public function test_disabling_catalogue_hides_navigation_and_dashboard_card(): void
    {
        [$business, $owner] = $this->createTenantWithUser('owner');
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // Disable catalogue module
        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $business,
            modules: [BusinessModule::Catalogue->value => false]
        );

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));

        $response->assertOk();
        // Navigation links and section must be hidden
        $response->assertDontSee('Items Catalogue');
        $response->assertDontSee(route('owner.catalogue.items.index'));
        $response->assertDontSee(route('owner.catalogue.categories.index'));
        $response->assertDontSee(route('owner.catalogue.brands.index'));
        $response->assertDontSee(route('owner.catalogue.units.index'));
        // Dashboard shortcuts card must be hidden
        $response->assertDontSee('Product Catalogue');
    }

    public function test_disabled_catalogue_denies_direct_url_access_with_403(): void
    {
        [$business, $owner] = $this->createTenantWithUser('owner');
        $platformAdmin = User::factory()->withPlatformRole()->create();

        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $business,
            modules: [BusinessModule::Catalogue->value => false]
        );

        // Server-side route authorization must strictly deny direct URL requests
        $this->actingAs($owner)->get(route('owner.catalogue.items.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.catalogue.categories.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.catalogue.brands.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.catalogue.units.index'))->assertStatus(403);
    }

    public function test_reenabling_catalogue_immediately_restores_navigation_and_access(): void
    {
        [$business, $owner] = $this->createTenantWithUser('owner');
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // 1. Disable
        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $business,
            modules: [BusinessModule::Catalogue->value => false]
        );

        $this->actingAs($owner)->get(route('owner.catalogue.items.index'))->assertStatus(403);

        // 2. Re-enable
        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $business,
            modules: [BusinessModule::Catalogue->value => true]
        );

        // 3. Immediately accessible on next request without stale authorization cache
        $this->actingAs($owner)->get(route('owner.catalogue.items.index'))->assertStatus(200);

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));
        $response->assertOk();
        $response->assertSee('Items Catalogue');
        $response->assertSee('Product Catalogue');
    }

    public function test_disabling_inventory_hides_all_inventory_navigation_entries(): void
    {
        [$business, $owner] = $this->createTenantWithUser('owner');
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // First confirm inventory links exist when enabled
        $activeResponse = $this->actingAs($owner)->get(route('owner.dashboard'));
        $activeResponse->assertSee('Stock Balances');
        $activeResponse->assertSee('Stock Ledger');
        $activeResponse->assertSee('Warehouses');
        $activeResponse->assertSee('Adjustments');
        $activeResponse->assertSee('Internal Transfers');
        $activeResponse->assertSee('Reconciliation');

        // Disable inventory module
        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $business,
            modules: [BusinessModule::Inventory->value => false]
        );

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));
        $response->assertOk();

        // All exclusively Inventory-owned navigation entries must be completely hidden
        $response->assertDontSee('Stock Balances');
        $response->assertDontSee('Stock Ledger');
        $response->assertDontSee(route('owner.inventory.stock.index'));
        $response->assertDontSee(route('owner.inventory.stock.ledger'));
        $response->assertDontSee(route('owner.inventory.warehouses.index'));
        $response->assertDontSee(route('owner.inventory.adjustments.index'));
        $response->assertDontSee(route('owner.inventory.transfers.index'));
        $response->assertDontSee(route('owner.inventory.stock.reconcile'));

        // Direct URL access blocked with 403
        $this->actingAs($owner)->get(route('owner.inventory.stock.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.inventory.stock.ledger'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.inventory.warehouses.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.inventory.adjustments.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.inventory.transfers.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.inventory.stock.reconcile'))->assertStatus(403);
    }

    public function test_read_only_inventory_permission_preserves_only_read_only_links(): void
    {
        // Manager role has business.view (access to Owner ERP), plus warehouses.view and inventory.view.
        // We override mutation permissions to Deny to test pure read-only state.
        [$business, $manager, $role] = $this->createTenantWithUser('manager');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();

        foreach (['inventory.manage', 'inventory.adjust', 'inventory.transfer', 'inventory.reconcile'] as $slug) {
            $perm = Permission::where('slug', $slug)->firstOrFail();
            $setOverride->execute(
                actor: $platformAdmin,
                business: $business,
                role: $role,
                permission: $perm,
                state: PermissionOverrideState::Deny
            );
        }

        $response = $this->actingAs($manager)->get(route('owner.dashboard'));
        $response->assertOk();

        // Read-only stock balances and warehouses links are preserved
        $response->assertSee('Stock Balances');
        $response->assertSee('Stock Ledger');
        $response->assertSee('Warehouses');

        // Mutation-oriented adjustment, transfer, and reconciliation links are omitted
        $response->assertDontSee('Adjustments');
        $response->assertDontSee('Internal Transfers');
        $response->assertDontSee('Reconciliation');
    }

    public function test_tenant_isolation_disabling_module_on_one_tenant_does_not_affect_another(): void
    {
        [$bizA, $ownerA] = $this->createTenantWithUser('owner');
        [$bizB, $ownerB] = $this->createTenantWithUser('owner');
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // Disable catalogue on Tenant A only
        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $bizA,
            modules: [BusinessModule::Catalogue->value => false]
        );

        // Tenant A has hidden links and 403 on direct route
        $respA = $this->actingAs($ownerA)->get(route('owner.dashboard'));
        $respA->assertDontSee('Items Catalogue');
        $this->actingAs($ownerA)->get(route('owner.catalogue.items.index'))->assertStatus(403);

        // Tenant B remains 100% active, visible, and accessible
        $respB = $this->actingAs($ownerB)->get(route('owner.dashboard'));
        $respB->assertSee('Items Catalogue');
        $this->actingAs($ownerB)->get(route('owner.catalogue.items.index'))->assertStatus(200);
    }

    public function test_denied_permission_hides_specific_navigation_link_without_hiding_entire_module(): void
    {
        [$business, $owner, $role] = $this->createTenantWithUser('owner');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // Deny purchases.manage specifically
        $perm = Permission::where('slug', 'purchases.manage')->firstOrFail();
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));
        $response->assertOk();

        // Purchase Orders navigation link must be hidden
        $response->assertDontSee(route('owner.purchasing.orders.index'));

        // Direct route access must return 403
        $this->actingAs($owner)->get(route('owner.purchasing.orders.index'))->assertStatus(403);

        // However, other allowed features in the procurement module (e.g. Suppliers) remain visible
        $response->assertSee(route('owner.purchasing.suppliers.index'));
        $response->assertSee('Suppliers');
    }

    public function test_disabled_module_stays_hidden_even_when_role_has_allow_override(): void
    {
        [$business, $manager, $role] = $this->createTenantWithUser('manager');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // 1. Grant explicit ALLOW override on purchases.manage for manager role
        $perm = Permission::where('slug', 'purchases.manage')->firstOrFail();
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );

        // 2. Disable Procurement module for the business
        $this->moduleSettingsAction->execute(
            actor: $platformAdmin,
            business: $business,
            modules: [BusinessModule::Procurement->value => false]
        );

        // 3. Navigation link must remain hidden because module is disabled
        $response = $this->actingAs($manager)->get(route('owner.dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('owner.purchasing.orders.index'));

        // 4. Direct route access must be denied with 403
        $this->actingAs($manager)->get(route('owner.purchasing.orders.index'))->assertStatus(403);
    }

    public function test_inherit_reflects_effective_role_permission(): void
    {
        [$business, $owner, $role] = $this->createTenantWithUser('owner');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();
        $perm = Permission::where('slug', 'inventory.reconcile')->firstOrFail();

        // Baseline: Owner canonically inherits inventory.reconcile -> visible
        $resp1 = $this->actingAs($owner)->get(route('owner.dashboard'));
        $resp1->assertSee('Reconciliation');
        $resp1->assertSee(route('owner.inventory.stock.reconcile'));

        // Override to DENY -> hidden
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        $resp2 = $this->actingAs($owner)->get(route('owner.dashboard'));
        $resp2->assertDontSee('Reconciliation');
        $resp2->assertDontSee(route('owner.inventory.stock.reconcile'));
        $this->actingAs($owner)->get(route('owner.inventory.stock.reconcile'))->assertStatus(403);

        // Override back to INHERIT -> restored
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Inherit
        );

        $resp3 = $this->actingAs($owner)->get(route('owner.dashboard'));
        $resp3->assertSee('Reconciliation');
        $resp3->assertSee(route('owner.inventory.stock.reconcile'));
        $this->actingAs($owner)->get(route('owner.inventory.stock.reconcile'))->assertStatus(200);
    }

    public function test_denying_opening_stock_permission_hides_opening_stock_button_on_stock_index(): void
    {
        [$business, $owner, $role] = $this->createTenantWithUser('owner');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // Baseline: Owner sees Opening Stock button
        $resp1 = $this->actingAs($owner)->get(route('owner.inventory.stock.index'));
        $resp1->assertOk();
        $resp1->assertSee('Opening Stock');
        $resp1->assertSee(route('owner.inventory.stock.opening_stock'));

        // Deny inventory.opening_stock
        $perm = Permission::where('slug', 'inventory.opening_stock')->firstOrFail();
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        $resp2 = $this->actingAs($owner)->get(route('owner.inventory.stock.index'));
        $resp2->assertOk();
        $resp2->assertDontSee('Record Opening Stock');
        $resp2->assertDontSee(route('owner.inventory.stock.opening_stock'));
        // Stock Adjustment remains visible
        $resp2->assertSee('Stock Adjustment');

        // Direct route access is denied
        $this->actingAs($owner)->get(route('owner.inventory.stock.opening_stock'))->assertStatus(403);
    }

    public function test_denying_post_bill_permission_hides_post_button_on_bill_show(): void
    {
        [$business, $owner, $role] = $this->createTenantWithUser('owner');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();

        $supplier = app(CreateSupplier::class)->execute($owner, $business, [
            'name' => 'Test Supplier',
        ]);
        $unit = Unit::factory()->create(['business_id' => $business->id]);
        $item = Item::factory()->create([
            'business_id' => $business->id,
            'unit_id' => $unit->id,
        ]);

        $bill = app(CreatePurchaseBill::class)->execute($owner, $business, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'INV-001',
            'lines' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 10,
                    'unit_cost' => 50,
                ],
            ],
        ]);

        // Baseline: Post Bill button visible on draft
        $resp1 = $this->actingAs($owner)->get(route('owner.purchasing.bills.show', $bill));
        $resp1->assertOk();
        $resp1->assertSee('Post Bill to Subledger');

        // Deny purchases.bills.post
        $perm = Permission::where('slug', 'purchases.bills.post')->firstOrFail();
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        $resp2 = $this->actingAs($owner)->get(route('owner.purchasing.bills.show', $bill));
        $resp2->assertOk();
        $resp2->assertDontSee('Post Bill to Subledger');

        // Direct POST mutation is blocked
        $this->actingAs($owner)->post(route('owner.purchasing.bills.post', $bill))->assertStatus(403);
    }

    public function test_denying_supplier_manage_hides_new_supplier_button_on_supplier_index(): void
    {
        [$business, $owner, $role] = $this->createTenantWithUser('owner');
        $setOverride = app(SetBusinessRolePermissionOverride::class);
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // Baseline: New Supplier button visible
        $resp1 = $this->actingAs($owner)->get(route('owner.purchasing.suppliers.index'));
        $resp1->assertOk();
        $resp1->assertSee('New Supplier');
        $resp1->assertSee(route('owner.purchasing.suppliers.create'));

        // Deny suppliers.manage
        $perm = Permission::where('slug', 'suppliers.manage')->firstOrFail();
        $setOverride->execute(
            actor: $platformAdmin,
            business: $business,
            role: $role,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        $resp2 = $this->actingAs($owner)->get(route('owner.purchasing.suppliers.index'));
        $resp2->assertOk();
        $resp2->assertDontSee('New Supplier');
        $resp2->assertDontSee(route('owner.purchasing.suppliers.create'));

        // Direct GET access is blocked with 403
        $this->actingAs($owner)->get(route('owner.purchasing.suppliers.create'))->assertStatus(403);
    }
}
