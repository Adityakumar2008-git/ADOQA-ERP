<?php

namespace Tests\Feature\Rbac;

use App\Actions\Rbac\AssignRoleToMember;
use App\Actions\Rbac\CreateBusinessRole;
use App\Actions\Rbac\DeleteBusinessRole;
use App\Actions\Rbac\RemoveRoleFromMember;
use App\Actions\Rbac\SyncRolePermissions;
use App\Actions\Rbac\UpdateBusinessRole;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\RoleScope;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleAdministrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    /**
     * Helper to provision a business and user with a specific role.
     */
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

    // ==========================================
    // 1. ROLE VISIBILITY & CROSS-TENANT ISOLATION
    // ==========================================

    public function test_business_a_cannot_view_business_b_custom_role(): void
    {
        [$ownerA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        [$ownerB, $businessB] = $this->createMemberWithRole('owner', isOwner: true);

        $customRoleB = Role::create([
            'business_id' => $businessB->id,
            'name' => 'Secret Supervisor B',
            'slug' => 'secret_supervisor_b',
            'scope' => RoleScope::Business,
        ]);

        $this->actingAs($ownerA);

        // GET /tenant/roles should list only business A custom roles + canonical system roles
        $indexResponse = $this->getJson('/tenant/roles');
        $indexResponse->assertStatus(200);
        $roleIds = collect($indexResponse->json('roles'))->pluck('id');
        $this->assertFalse($roleIds->contains($customRoleB->id));

        // GET /tenant/roles/{customRoleB} directly should be rejected with 403
        $showResponse = $this->getJson("/tenant/roles/{$customRoleB->id}");
        $showResponse->assertStatus(403);
    }

    public function test_business_a_cannot_modify_business_b_role(): void
    {
        [$ownerA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        [$ownerB, $businessB] = $this->createMemberWithRole('owner', isOwner: true);

        $customRoleB = Role::create([
            'business_id' => $businessB->id,
            'name' => 'Store B Role',
            'slug' => 'store_b_role',
            'scope' => RoleScope::Business,
        ]);

        $this->actingAs($ownerA);

        // HTTP attempt
        $response = $this->putJson("/tenant/roles/{$customRoleB->id}", [
            'name' => 'Compromised Name',
        ]);
        $response->assertStatus(500); // Throws DomainException on cross-tenant modification

        // Direct action invocation attempt
        $action = app(UpdateBusinessRole::class);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot modify role belonging to business/');

        $action->execute($ownerA, $businessA, $customRoleB, ['name' => 'Compromised Name']);
    }

    public function test_business_a_cannot_delete_business_b_role(): void
    {
        [$ownerA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        [$ownerB, $businessB] = $this->createMemberWithRole('owner', isOwner: true);

        $customRoleB = Role::create([
            'business_id' => $businessB->id,
            'name' => 'Store B Role',
            'slug' => 'store_b_role',
            'scope' => RoleScope::Business,
        ]);

        $action = app(DeleteBusinessRole::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot delete role belonging to business/');

        $action->execute($ownerA, $businessA, $customRoleB);
    }

    // ==========================================
    // 2. ROLE CREATION
    // ==========================================

    public function test_authorized_actor_can_create_valid_custom_business_role(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $this->actingAs($owner);

        $perm = Permission::where('slug', 'inventory.manage')->firstOrFail();

        $response = $this->postJson('/tenant/roles', [
            'name' => 'Inventory Auditor',
            'slug' => 'inv-auditor',
            'description' => 'Audits warehouse items',
            'permissions' => [$perm->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('role.name', 'Inventory Auditor');
        $response->assertJsonPath('role.slug', 'inv-auditor');
        $response->assertJsonPath('role.business_id', $business->id);

        $createdRole = Role::where('slug', 'inv-auditor')->where('business_id', $business->id)->firstOrFail();
        $this->assertTrue($createdRole->permissions->contains($perm));

        // Audit log verified
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'action' => 'role.created',
            'auditable_type' => Role::class,
            'auditable_id' => $createdRole->id,
        ]);
    }

    public function test_unauthorized_actor_cannot_create_role(): void
    {
        [$staff, $business] = $this->createMemberWithRole('staff');

        $this->actingAs($staff);

        $response = $this->postJson('/tenant/roles', [
            'name' => 'Attempted Supervisor',
        ]);

        $response->assertStatus(403);
    }

    public function test_business_user_cannot_create_platform_role(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $action = app(CreateBusinessRole::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Businesses cannot create platform-scoped roles.');

        $action->execute($owner, $business, [
            'name' => 'Platform Admin Attempt',
            'scope' => 'platform',
        ]);
    }

    // ==========================================
    // 3. PERMISSION ATTACHMENT & SCOPE VALIDATION
    // ==========================================

    public function test_valid_business_permission_can_attach_to_business_role(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $role = Role::create([
            'business_id' => $business->id,
            'name' => 'Floor Lead',
            'slug' => 'floor_lead',
            'scope' => RoleScope::Business,
        ]);

        $perm1 = Permission::where('slug', 'sales.create')->firstOrFail();
        $perm2 = Permission::where('slug', 'invoices.view')->firstOrFail();

        $action = app(SyncRolePermissions::class);
        $action->execute($owner, $business, $role, [$perm1->slug, $perm2->slug]);

        $this->assertEquals(2, $role->permissions()->count());
        $this->assertTrue($role->permissions->contains($perm1));
        $this->assertTrue($role->permissions->contains($perm2));
    }

    public function test_platform_permission_cannot_attach_to_business_role(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $role = Role::create([
            'business_id' => $business->id,
            'name' => 'Store Role',
            'slug' => 'store_role',
            'scope' => RoleScope::Business,
        ]);

        $platformPerm = Permission::where('slug', 'businesses.create')->firstOrFail();
        $action = app(SyncRolePermissions::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot attach platform permission/');

        $action->execute($owner, $business, $role, [$platformPerm->slug]);
    }

    // ==========================================
    // 4. ROLE ASSIGNMENT & TENANT INVARIANTS
    // ==========================================

    public function test_valid_target_member_can_receive_valid_role(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser] = $this->createMemberWithRole('staff', $business);

        $managerRole = Role::where('slug', 'manager')->whereNull('business_id')->firstOrFail();

        $this->actingAs($owner);

        $response = $this->postJson("/tenant/members/{$staffUser->id}/roles", [
            'role_id' => $managerRole->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $staffUser->id,
            'role_id' => $managerRole->id,
            'business_id' => $business->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'action' => 'role.assigned',
        ]);
    }

    public function test_cross_business_target_member_assignment_rejected(): void
    {
        [$ownerA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffB, $businessB] = $this->createMemberWithRole('staff');

        $managerRole = Role::where('slug', 'manager')->whereNull('business_id')->firstOrFail();
        $action = app(AssignRoleToMember::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Target user does not belong to business/');

        $action->execute($ownerA, $businessA, $staffB, $managerRole);
    }

    public function test_cross_business_role_assignment_rejected(): void
    {
        [$ownerA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffA] = $this->createMemberWithRole('staff', $businessA);
        $businessB = Business::factory()->create();

        $roleB = Role::create([
            'business_id' => $businessB->id,
            'name' => 'Store B Role',
            'slug' => 'role_b',
            'scope' => RoleScope::Business,
        ]);

        $action = app(AssignRoleToMember::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot assign role owned by business/');

        $action->execute($ownerA, $businessA, $staffA, $roleB);
    }

    public function test_inactive_member_assignment_rejected(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , $staffMember] = $this->createMemberWithRole('staff', $business);

        $staffMember->update(['status' => MembershipStatus::Inactive]);

        $role = Role::where('slug', 'cashier')->whereNull('business_id')->firstOrFail();
        $action = app(AssignRoleToMember::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot assign roles to a member with suspended or inactive membership.');

        $action->execute($owner, $business, $staffUser, $role);
    }

    public function test_suspended_member_assignment_rejected(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staffUser, , $staffMember] = $this->createMemberWithRole('staff', $business);

        $staffMember->update(['status' => MembershipStatus::Suspended]);

        $role = Role::where('slug', 'cashier')->whereNull('business_id')->firstOrFail();
        $action = app(AssignRoleToMember::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot assign roles to a member with suspended or inactive membership.');

        $action->execute($owner, $business, $staffUser, $role);
    }

    // ==========================================
    // 5. PRIVILEGE ESCALATION PROTECTION
    // ==========================================

    public function test_staff_cannot_escalate_to_manager(): void
    {
        [$staff, $business] = $this->createMemberWithRole('staff');
        [$targetMember] = $this->createMemberWithRole('cashier', $business);

        $this->actingAs($staff);

        $managerRole = Role::where('slug', 'manager')->whereNull('business_id')->firstOrFail();

        $response = $this->postJson("/tenant/members/{$targetMember->id}/roles", [
            'role_id' => $managerRole->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_cashier_cannot_escalate_to_owner(): void
    {
        [$cashier, $business] = $this->createMemberWithRole('cashier');
        [$targetMember] = $this->createMemberWithRole('staff', $business);

        $this->actingAs($cashier);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();

        $response = $this->postJson("/tenant/members/{$targetMember->id}/roles", [
            'role_id' => $ownerRole->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_self_role_escalation_blocked(): void
    {
        [$manager, $business] = $this->createMemberWithRole('manager');

        $this->actingAs($manager);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();

        $action = app(AssignRoleToMember::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Self-escalation blocked: Users cannot assign or modify their own roles.');

        $action->execute($manager, $business, $manager, $ownerRole);
    }

    public function test_manager_cannot_grant_unauthorized_privileges_to_custom_role(): void
    {
        [$manager, $business] = $this->createMemberWithRole('manager');

        // Manager has staff.manage, but DOES NOT have settings.manage
        $customRole = Role::create([
            'business_id' => $business->id,
            'name' => 'Custom Assistant',
            'slug' => 'custom_assistant',
            'scope' => RoleScope::Business,
        ]);

        $settingsManagePerm = Permission::where('slug', 'settings.manage')->firstOrFail();

        $action = app(SyncRolePermissions::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Privilege escalation blocked: Cannot grant permission \[settings\.manage\]/');

        // Manager attempts to give settings.manage to customRole
        $action->execute($manager, $business, $customRole, [$settingsManagePerm->slug]);
    }

    // ==========================================
    // 6. SYSTEM ROLE PROTECTION
    // ==========================================

    public function test_system_role_cannot_be_deleted(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $cashierRole = Role::where('slug', 'cashier')->whereNull('business_id')->firstOrFail();

        $action = app(DeleteBusinessRole::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Canonical system role [cashier] cannot be deleted.');

        $action->execute($owner, $business, $cashierRole);
    }

    public function test_system_role_cannot_be_renamed_or_modified(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $cashierRole = Role::where('slug', 'cashier')->whereNull('business_id')->firstOrFail();

        $action = app(UpdateBusinessRole::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Canonical system role [cashier] cannot be renamed, converted, or modified.');

        $action->execute($owner, $business, $cashierRole, ['name' => 'Altered Cashier']);
    }

    public function test_canonical_owner_permissions_cannot_be_modified(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();

        $action = app(SyncRolePermissions::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Canonical system role [owner] permissions are fixed and cannot be modified.');

        $action->execute($owner, $business, $ownerRole, ['sales.create']);
    }

    public function test_primary_owner_role_cannot_be_unassigned_from_owner(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$otherStaff] = $this->createMemberWithRole('manager', $business);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        $action = app(RemoveRoleFromMember::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot remove the primary owner role from the business owner.');

        // Other staff attempting to remove owner role from primary owner
        $action->execute($otherStaff, $business, $owner, $ownerRole);
    }

    // ==========================================
    // 7. PLATFORM ROLE ADMINISTRATION
    // ==========================================

    public function test_platform_admin_can_view_platform_roles(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();

        $this->actingAs($platformAdmin);

        $response = $this->getJson('/admin/roles');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'roles',
            'permissions',
        ]);
        $roleSlugs = collect($response->json('roles'))->pluck('slug');
        $this->assertTrue($roleSlugs->contains('platform_admin'));
    }

    public function test_ordinary_business_user_cannot_view_platform_roles(): void
    {
        [$owner] = $this->createMemberWithRole('owner', isOwner: true);

        $this->actingAs($owner);

        $response = $this->getJson('/admin/roles');

        $response->assertStatus(403);
    }

    public function test_platform_admin_can_view_platform_roles_html_page(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();

        $response = $this->actingAs($platformAdmin)->get('/admin/roles');

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.index');
        $response->assertSee('Roles & Permissions');
        $response->assertSee('Platform Administrator');
        $response->assertSee('platform.roles.manage');
        $response->assertDontSee('{"roles":');
    }

    public function test_ordinary_business_user_cannot_view_platform_roles_html_page(): void
    {
        [$owner] = $this->createMemberWithRole('owner', isOwner: true);

        $response = $this->actingAs($owner)->get('/admin/roles');

        $response->assertStatus(403);
    }

    public function test_platform_admin_can_access_dedicated_roles_api_endpoint(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();

        $response = $this->actingAs($platformAdmin)->get(route('admin.roles.api'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'roles',
            'permissions',
        ]);
        $roleSlugs = collect($response->json('roles'))->pluck('slug');
        $this->assertTrue($roleSlugs->contains('platform_admin'));
    }

    public function test_ordinary_business_user_cannot_access_roles_api_endpoint(): void
    {
        [$owner] = $this->createMemberWithRole('owner', isOwner: true);

        $response = $this->actingAs($owner)->get(route('admin.roles.api'));

        $response->assertStatus(403);
    }
}
