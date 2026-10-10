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

class PermissionAwareVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected AuthorizationService $authService;

    protected SetBusinessRolePermissionOverride $setOverride;

    protected UpdateBusinessModuleSettings $moduleSettingsAction;

    protected User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->authService = app(AuthorizationService::class);
        $this->setOverride = app(SetBusinessRolePermissionOverride::class);
        $this->moduleSettingsAction = app(UpdateBusinessModuleSettings::class);
        $this->platformAdmin = User::factory()->withPlatformRole()->create();
    }

    protected function createTenantWithOwner(): array
    {
        $business = Business::factory()->create([
            'status' => BusinessStatus::Active,
        ]);

        $owner = User::factory()->create([
            'status' => UserStatus::Active,
            'is_platform_admin' => false,
        ]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();

        UserRole::create([
            'user_id' => $owner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $business->id,
        ]);

        return [$business, $owner, $ownerRole];
    }

    public function test_inventory_adjust_deny_hides_stock_adjustment_button_and_protects_route(): void
    {
        [$business, $owner, $ownerRole] = $this->createTenantWithOwner();

        // 1. Initial baseline: Owner has inventory.adjust by default
        $response = $this->actingAs($owner)->get(route('owner.inventory.stock.index'));
        $response->assertOk();
        $response->assertSee('Stock Adjustment');
        $response->assertSee(route('owner.inventory.adjustments.create'));

        // 2. Override inventory.adjust to DENY
        $permAdjust = Permission::where('slug', 'inventory.adjust')->firstOrFail();
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permAdjust,
            state: PermissionOverrideState::Deny
        );

        $this->authService->clearMemoized();

        // 3. Verify on stock page: Stock Adjustment button is absent!
        $responseDenied = $this->actingAs($owner)->get(route('owner.inventory.stock.index'));
        $responseDenied->assertOk();
        $responseDenied->assertDontSee(route('owner.inventory.adjustments.create'));

        // 4. Other stock-page actions remain visible: Stock Ledger & Opening Stock
        $responseDenied->assertSee('Stock Ledger');
        $responseDenied->assertSee(route('owner.inventory.stock.ledger'));
        $responseDenied->assertSee('Opening Stock');
        $responseDenied->assertSee(route('owner.inventory.stock.opening_stock'));

        // 5. Verify on adjustments index: New Adjustment button and empty-state action are absent
        $responseAdj = $this->actingAs($owner)->get(route('owner.inventory.adjustments.index'));
        $responseAdj->assertOk();
        $responseAdj->assertDontSee(route('owner.inventory.adjustments.create'));

        // 6. Direct server-side request is rejected with 403 Forbidden
        $this->actingAs($owner)->get(route('owner.inventory.adjustments.create'))->assertStatus(403);
        $this->actingAs($owner)->post(route('owner.inventory.adjustments.store'), [])->assertStatus(403);
    }

    public function test_inventory_adjust_inherit_or_allow_restores_button_visibility(): void
    {
        [$business, $owner, $ownerRole] = $this->createTenantWithOwner();
        $permAdjust = Permission::where('slug', 'inventory.adjust')->firstOrFail();

        // Step 1: DENY
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permAdjust,
            state: PermissionOverrideState::Deny
        );
        $this->authService->clearMemoized();
        $this->actingAs($owner)->get(route('owner.inventory.stock.index'))
            ->assertDontSee(route('owner.inventory.adjustments.create'));

        // Step 2: Restore to INHERIT (for Owner role, default is allow)
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permAdjust,
            state: PermissionOverrideState::Inherit
        );
        $this->authService->clearMemoized();
        $this->actingAs($owner)->get(route('owner.inventory.stock.index'))
            ->assertSee(route('owner.inventory.adjustments.create'));

        // Step 3: Explicit ALLOW
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permAdjust,
            state: PermissionOverrideState::Allow
        );
        $this->authService->clearMemoized();
        $this->actingAs($owner)->get(route('owner.inventory.stock.index'))
            ->assertSee(route('owner.inventory.adjustments.create'));
    }

    public function test_disabled_inventory_module_blocks_access_even_when_override_is_allow(): void
    {
        [$business, $owner, $ownerRole] = $this->createTenantWithOwner();
        $permAdjust = Permission::where('slug', 'inventory.adjust')->firstOrFail();

        // Explicit ALLOW on inventory.adjust
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permAdjust,
            state: PermissionOverrideState::Allow
        );

        // Disable Inventory module
        $this->moduleSettingsAction->execute(
            actor: $this->platformAdmin,
            business: $business,
            modules: [BusinessModule::Inventory->value => false]
        );

        $this->authService->clearMemoized();

        // Direct access to stock page is forbidden
        $this->actingAs($owner)->get(route('owner.inventory.stock.index'))->assertStatus(403);
        $this->actingAs($owner)->get(route('owner.inventory.adjustments.create'))->assertStatus(403);

        // Sidebar hides inventory navigation completely
        $response = $this->actingAs($owner)->get(route('owner.dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('owner.inventory.stock.index'));
        $response->assertDontSee(route('owner.inventory.adjustments.index'));
    }

    public function test_inventory_transfer_deny_hides_transfers_link_and_actions(): void
    {
        [$business, $owner, $ownerRole] = $this->createTenantWithOwner();

        // Deny inventory.transfer
        $permTransfer = Permission::where('slug', 'inventory.transfer')->firstOrFail();
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permTransfer,
            state: PermissionOverrideState::Deny
        );

        $this->authService->clearMemoized();

        // Sidebar does NOT show Transfers link
        $response = $this->actingAs($owner)->get(route('owner.inventory.stock.index'));
        $response->assertOk();
        $response->assertDontSee(route('owner.inventory.transfers.index'));

        // But Stock Balances and Warehouses remain visible
        $response->assertSee(route('owner.inventory.stock.index'));
        $response->assertSee(route('owner.inventory.warehouses.index'));

        // Direct transfer create is rejected
        $this->actingAs($owner)->get(route('owner.inventory.transfers.create'))->assertStatus(403);
    }

    public function test_purchases_bills_manage_deny_hides_create_bill_button(): void
    {
        [$business, $owner, $ownerRole] = $this->createTenantWithOwner();

        // Baseline: Create button visible
        $response = $this->actingAs($owner)->get(route('owner.purchasing.bills.index'));
        $response->assertOk();
        $response->assertSee('New Purchase Bill');

        // Deny purchases.bills.manage
        $permBills = Permission::where('slug', 'purchases.bills.manage')->firstOrFail();
        $this->setOverride->execute(
            actor: $this->platformAdmin,
            business: $business,
            role: $ownerRole,
            permission: $permBills,
            state: PermissionOverrideState::Deny
        );
        $this->authService->clearMemoized();

        // Direct access to bills create is rejected
        $this->actingAs($owner)->get(route('owner.purchasing.bills.create'))->assertStatus(403);

        // Index page hides the New Purchase Bill link
        $responseBills = $this->actingAs($owner)->get(route('owner.purchasing.bills.index'));
        if ($responseBills->status() === 200) {
            $responseBills->assertDontSee(route('owner.purchasing.bills.create'));
        }
    }
}
