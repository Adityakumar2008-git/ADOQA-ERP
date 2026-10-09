<?php

namespace Tests\Feature\Tenancy;

use App\Actions\Tenancy\SetBusinessRolePermissionOverride;
use App\Actions\Tenancy\UpdateBusinessModuleSettings;
use App\Enums\BusinessModule;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\PermissionOverrideState;
use App\Enums\RoleScope;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\ModuleRegistry;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TenantAccessControlTest extends TestCase
{
    use DatabaseTransactions;

    protected AuthorizationService $authService;

    protected ModuleRegistry $moduleRegistry;

    protected SetBusinessRolePermissionOverride $setOverrideAction;

    protected UpdateBusinessModuleSettings $updateModulesAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->authService = app(AuthorizationService::class);
        $this->moduleRegistry = app(ModuleRegistry::class);
        $this->setOverrideAction = app(SetBusinessRolePermissionOverride::class);
        $this->updateModulesAction = app(UpdateBusinessModuleSettings::class);
    }

    protected function createMemberWithRole(string $roleSlug, ?Business $business = null, bool $isOwner = false): array
    {
        $business ??= Business::factory()->create(['status' => BusinessStatus::Active]);
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $member = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => $isOwner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $role = Role::where('slug', $roleSlug)->whereNull('business_id')->firstOrFail();

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        return [$user, $business, $member, $userRole, $role];
    }

    public function test_tenant_role_inherits_canonical_permissions_by_default(): void
    {
        [$user, $business, $member, $userRole, $role] = $this->createMemberWithRole('manager');

        // Canonical manager role possesses inventory.manage and inventory.view
        $this->assertTrue($this->authService->can($user, 'inventory.manage', $business));
        $this->assertTrue($this->authService->can($user, 'inventory.view', $business));

        // Canonical manager does not possess settings.manage or platform permissions
        $this->assertFalse($this->authService->can($user, 'settings.manage', $business));
        $this->assertFalse($this->authService->can($user, 'businesses.create', $business));
    }

    public function test_explicit_allow_override_grants_permission_to_role_in_tenant(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , , , $staffRole] = $this->createMemberWithRole('staff', $business);

        // Canonical staff does NOT have catalogue.manage
        $this->assertFalse($this->authService->can($staffUser, 'catalogue.manage', $business));

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // Owner configures explicit ALLOW for staff role in this business
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $staffRole,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );

        // Staff user now possesses catalogue.manage in this business
        $this->assertTrue($this->authService->can($staffUser, 'catalogue.manage', $business));
    }

    public function test_explicit_deny_override_suppresses_template_permission_in_tenant(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$managerUser, , , , $managerRole] = $this->createMemberWithRole('manager', $business);

        // Canonical manager has inventory.adjust
        $this->assertTrue($this->authService->can($managerUser, 'inventory.adjust', $business));

        $perm = Permission::where('slug', 'inventory.adjust')->firstOrFail();

        // Owner configures explicit DENY for manager role in this business
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $managerRole,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        // Manager user is now denied inventory.adjust in this business
        $this->assertFalse($this->authService->can($managerUser, 'inventory.adjust', $business));
    }

    public function test_disabled_tenant_module_denies_all_protected_operations_on_server(): void
    {
        [$owner, $business, , , $ownerRole] = $this->createMemberWithRole('owner', isOwner: true);

        // Owner normally has inventory.manage, inventory.view, inventory.transfer
        $this->assertTrue($this->authService->can($owner, 'inventory.manage', $business));
        $this->assertTrue($this->authService->can($owner, 'inventory.transfer', $business));

        // 1. Explicit ALLOW override on inventory.manage for owner role
        $perm = Permission::where('slug', 'inventory.manage')->firstOrFail();
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $ownerRole,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );
        $this->assertTrue($this->authService->can($owner, 'inventory.manage', $business));

        // 2. Disable the inventory module for this business
        $this->updateModulesAction->execute(
            actor: $owner,
            business: $business,
            modules: [BusinessModule::Inventory->value => false]
        );

        // Even though Owner role has inventory.manage in template AND explicit ALLOW override, server denies it immediately
        $this->assertFalse($this->authService->can($owner, 'inventory.manage', $business));
        $this->assertFalse($this->authService->can($owner, 'inventory.transfer', $business));
        $this->assertFalse($this->authService->can($owner, 'inventory.adjust', $business));

        // Other modules (e.g. catalogue) remain accessible
        $this->assertTrue($this->authService->can($owner, 'catalogue.manage', $business));
    }

    public function test_explicit_allow_cannot_bypass_inactive_user(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , , , $staffRole] = $this->createMemberWithRole('staff', $business);

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $staffRole,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );

        // Verify active staff has permission
        $this->assertTrue($this->authService->can($staffUser, 'catalogue.manage', $business));

        // Deactivate user
        $staffUser->update(['status' => UserStatus::Suspended]);
        $this->authService->clearMemoized();

        $this->assertFalse($this->authService->can($staffUser, 'catalogue.manage', $business));
    }

    public function test_explicit_allow_cannot_bypass_inactive_tenant(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , , , $staffRole] = $this->createMemberWithRole('staff', $business);

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $staffRole,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );

        // Suspend business
        $business->update(['status' => BusinessStatus::Suspended]);
        $this->authService->clearMemoized();

        $this->assertFalse($this->authService->can($staffUser, 'catalogue.manage', $business));
    }

    public function test_explicit_allow_cannot_bypass_inactive_membership(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , $staffMember, , $staffRole] = $this->createMemberWithRole('staff', $business);

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $staffRole,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );

        // Deactivate membership
        $staffMember->update(['status' => MembershipStatus::Inactive]);
        $this->authService->clearMemoized();

        $this->assertFalse($this->authService->can($staffUser, 'catalogue.manage', $business));
    }

    public function test_overrides_in_tenant_a_do_not_affect_tenant_b(): void
    {
        [$ownerA, $bizA] = $this->createMemberWithRole('owner', isOwner: true);
        [$managerA, , , , $managerRole] = $this->createMemberWithRole('manager', $bizA);

        [$ownerB, $bizB] = $this->createMemberWithRole('owner', isOwner: true);
        [$managerB] = $this->createMemberWithRole('manager', $bizB);

        $perm = Permission::where('slug', 'inventory.adjust')->firstOrFail();

        // Business A sets DENY on inventory.adjust for manager
        $this->setOverrideAction->execute(
            actor: $ownerA,
            business: $bizA,
            role: $managerRole,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        // Manager in Business A is denied
        $this->assertFalse($this->authService->can($managerA, 'inventory.adjust', $bizA));

        // Manager in Business B is NOT affected and remains permitted
        $this->assertTrue($this->authService->can($managerB, 'inventory.adjust', $bizB));

        // Business A disables module Catalogue
        $this->updateModulesAction->execute(
            actor: $ownerA,
            business: $bizA,
            modules: [BusinessModule::Catalogue->value => false]
        );

        $this->assertFalse($this->authService->can($ownerA, 'catalogue.manage', $bizA));
        $this->assertTrue($this->authService->can($ownerB, 'catalogue.manage', $bizB));
    }

    public function test_platform_scoped_roles_and_permissions_are_strictly_rejected_from_overrides(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $platformRole = Role::where('scope', RoleScope::Platform)->firstOrFail();
        $businessPerm = Permission::where('scope', RoleScope::Business)->firstOrFail();

        // 1. Setting override on platform-scoped role throws DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/platform-scoped role/i');
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $platformRole,
            permission: $businessPerm,
            state: PermissionOverrideState::Allow
        );
    }

    public function test_platform_scoped_permissions_are_strictly_rejected_from_overrides(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $platformPerm = Permission::where('scope', RoleScope::Platform)->firstOrFail();
        $businessRole = Role::where('scope', RoleScope::Business)->whereNull('business_id')->firstOrFail();

        // 2. Setting override on platform-scoped permission throws DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/platform-scoped permission/i');
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $businessRole,
            permission: $platformPerm,
            state: PermissionOverrideState::Allow
        );
    }

    public function test_cross_tenant_role_override_is_rejected(): void
    {
        [$ownerA, $bizA] = $this->createMemberWithRole('owner', isOwner: true);
        [$ownerB, $bizB] = $this->createMemberWithRole('owner', isOwner: true);

        // Create a custom role belonging strictly to Business B
        $customRoleB = Role::create([
            'business_id' => $bizB->id,
            'name' => 'Custom B Role',
            'slug' => 'custom-b-role',
            'scope' => RoleScope::Business,
            'is_system' => false,
        ]);

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot modify role belonging to business/i');

        // Attempting to configure override for Business B's role under Business A context
        $this->setOverrideAction->execute(
            actor: $ownerA,
            business: $bizA,
            role: $customRoleB,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );
    }

    public function test_inheriting_default_deletes_override_record(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , , , $staffRole] = $this->createMemberWithRole('staff', $business);

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // 1. Explicit ALLOW
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $staffRole,
            permission: $perm,
            state: PermissionOverrideState::Allow
        );

        $this->assertTrue($this->authService->can($staffUser, 'catalogue.manage', $business));
        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $staffRole->id,
            'permission_id' => $perm->id,
            'state' => 'allow',
        ]);

        // 2. Set to Inherit
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $staffRole,
            permission: $perm,
            state: PermissionOverrideState::Inherit
        );

        $this->assertDatabaseMissing('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $staffRole->id,
            'permission_id' => $perm->id,
        ]);

        // Reverts to default (staff cannot catalogue.manage)
        $this->assertFalse($this->authService->can($staffUser, 'catalogue.manage', $business));
    }

    public function test_audit_log_recorded_for_module_and_override_updates(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $managerRole = Role::where('slug', 'manager')->whereNull('business_id')->firstOrFail();
        $perm = Permission::where('slug', 'inventory.adjust')->firstOrFail();

        // Override audit
        $this->setOverrideAction->execute(
            actor: $owner,
            business: $business,
            role: $managerRole,
            permission: $perm,
            state: PermissionOverrideState::Deny
        );

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'action' => 'role.permission_override_set',
            'auditable_type' => Role::class,
            'auditable_id' => $managerRole->id,
        ]);

        // Module update audit
        $this->updateModulesAction->execute(
            actor: $owner,
            business: $business,
            modules: [BusinessModule::Finance->value => false]
        );

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'action' => 'business.modules_updated',
            'auditable_type' => Business::class,
            'auditable_id' => $business->id,
        ]);
    }

    public function test_platform_admin_can_update_tenant_modules_via_http(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $response = $this->actingAs($platformAdmin)
            ->post(route('admin.businesses.modules.update', $business), [
                'modules' => [
                    'inventory' => 0,
                    'catalogue' => 1,
                ],
            ]);

        $response->assertRedirect(route('admin.businesses.show', $business));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('business_module_settings', [
            'business_id' => $business->id,
            'module_slug' => 'inventory',
            'is_enabled' => false,
        ]);
    }

    public function test_platform_admin_can_set_tenant_role_override_via_http(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $staffRole = Role::where('slug', 'staff')->whereNull('business_id')->firstOrFail();
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        $response = $this->actingAs($platformAdmin)
            ->post(route('admin.businesses.overrides.store', $business), [
                'role_id' => $staffRole->id,
                'permission_id' => $perm->id,
                'state' => 'allow',
            ]);

        $response->assertRedirect(route('admin.businesses.show', $business));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $staffRole->id,
            'permission_id' => $perm->id,
            'state' => 'allow',
        ]);
    }

    public function test_tenant_owner_can_inspect_and_update_modules_via_http(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        // Inspect modules
        $response = $this->actingAs($owner)
            ->withSession(['tenant_id' => $business->id])
            ->getJson(route('tenant.modules.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'modules' => [
                    '*' => ['slug', 'name', 'description', 'is_enabled', 'permissions'],
                ],
            ]);

        // Update modules
        $updateResponse = $this->actingAs($owner)
            ->withSession(['tenant_id' => $business->id])
            ->putJson(route('tenant.modules.update'), [
                'modules' => [
                    'inventory' => false,
                ],
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('modules.inventory', false);

        $this->assertDatabaseHas('business_module_settings', [
            'business_id' => $business->id,
            'module_slug' => 'inventory',
            'is_enabled' => false,
        ]);
    }

    public function test_tenant_owner_can_manage_role_overrides_via_http(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $staffRole = Role::where('slug', 'staff')->whereNull('business_id')->firstOrFail();
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['tenant_id' => $business->id])
            ->postJson(route('tenant.roles.overrides.store', $staffRole), [
                'permission_id' => $perm->id,
                'state' => 'allow',
            ]);

        $response->assertOk()
            ->assertJsonPath('override.state', 'allow');

        // Check index
        $indexResponse = $this->actingAs($owner)
            ->withSession(['tenant_id' => $business->id])
            ->getJson(route('tenant.roles.overrides.index', $staffRole));

        $indexResponse->assertOk()
            ->assertJsonPath('overrides.0.state', 'allow');
    }

    public function test_unauthorized_user_cannot_update_modules_or_overrides_via_http(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        [$staffUser] = $this->createMemberWithRole('staff', $business);
        $staffRole = Role::where('slug', 'staff')->whereNull('business_id')->firstOrFail();
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // Staff does not have business.update
        $this->actingAs($staffUser)
            ->withSession(['tenant_id' => $business->id])
            ->putJson(route('tenant.modules.update'), [
                'modules' => ['inventory' => false],
            ])
            ->assertForbidden();

        // Staff does not have staff.manage
        $this->actingAs($staffUser)
            ->withSession(['tenant_id' => $business->id])
            ->postJson(route('tenant.roles.overrides.store', $staffRole), [
                'permission_id' => $perm->id,
                'state' => 'allow',
            ])
            ->assertForbidden();
    }
}
