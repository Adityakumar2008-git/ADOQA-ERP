<?php

namespace Tests\Feature\Owner;

use App\Actions\Tenancy\SetBusinessRolePermissionOverride;
use App\Actions\Tenancy\UpdateBusinessModuleSettings;
use App\Enums\BusinessModule;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\PermissionOverrideState;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Permission;
use App\Models\Role;
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
}
