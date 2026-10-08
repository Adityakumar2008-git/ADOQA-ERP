<?php

namespace Tests\Feature\Authorization;

use App\Actions\Business\CreateBusiness;
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
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationEngineTest extends TestCase
{
    use DatabaseTransactions;

    protected AuthorizationService $authService;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->authService = app(AuthorizationService::class);
        $this->tenantContext = app(TenantContext::class);
    }

    /**
     * Helper to create a business and member with a specific canonical role.
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

        return [$user, $business, $member, $userRole];
    }

    // ==========================================
    // 1. AUTHENTICATION BOUNDARY
    // ==========================================

    public function test_guest_is_denied_access_to_protected_routes(): void
    {
        $response = $this->getJson('/tenant/current');
        $response->assertStatus(401);

        $response2 = $this->postJson('/admin/businesses', []);
        $response2->assertStatus(401);

        $this->assertFalse($this->authService->can(null, 'businesses.create'));
        $this->assertFalse($this->authService->can(null, 'sales.create'));
    }

    public function test_authenticated_user_without_permission_is_denied(): void
    {
        $user = User::factory()->create(); // Ordinary user with no roles
        $business = Business::factory()->create();

        $this->assertFalse($this->authService->can($user, 'businesses.create'));
        $this->assertFalse($this->authService->can($user, 'sales.create', $business));
    }

    // ==========================================
    // 2. PLATFORM AUTHORIZATION BOUNDARY
    // ==========================================

    public function test_platform_admin_with_correct_permission_is_allowed(): void
    {
        $admin = User::factory()->withPlatformRole()->create();

        $this->assertTrue($this->authService->can($admin, 'businesses.create'));
        $this->assertTrue($this->authService->can($admin, 'businesses.view'));
        $this->assertTrue($this->authService->can($admin, 'subscriptions.manage'));
        $this->assertTrue($this->authService->canAccessPlatform($admin));
    }

    public function test_platform_admin_lacking_specific_platform_permission_is_denied(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        // Create restricted custom platform role without businesses.create
        $limitedRole = Role::create([
            'business_id' => null,
            'name' => 'Limited Platform Viewer',
            'slug' => 'limited_viewer_'.Str::random(5),
            'scope' => RoleScope::Platform,
        ]);
        $viewPerm = Permission::where('slug', 'businesses.view')->firstOrFail();
        $limitedRole->permissions()->attach($viewPerm->id);

        UserRole::create([
            'user_id' => $admin->id,
            'role_id' => $limitedRole->id,
            'business_id' => null,
        ]);

        $this->assertTrue($this->authService->can($admin, 'businesses.view'));
        $this->assertFalse($this->authService->can($admin, 'businesses.create')); // Denied missing permission
        $this->assertFalse($this->authService->can($admin, 'businesses.suspend')); // Denied missing permission
    }

    public function test_ordinary_business_user_denied_platform_action(): void
    {
        [$user, $business] = $this->createMemberWithRole('staff');

        $this->assertFalse($this->authService->can($user, 'businesses.create'));
        $this->assertFalse($this->authService->canAccessPlatform($user));

        $this->actingAs($user);
        $response = $this->postJson('/admin/businesses', []);
        $response->assertStatus(403);
    }

    public function test_business_owner_denied_platform_only_permission(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        // Owner has full business permissions, but ZERO platform permissions
        $this->assertFalse($this->authService->can($owner, 'businesses.create'));
        $this->assertFalse($this->authService->can($owner, 'subscriptions.manage'));
        $this->assertFalse($this->authService->can($owner, 'platform.settings.manage'));
        $this->assertFalse($this->authService->canAccessPlatform($owner));

        $this->actingAs($owner);
        $response = $this->postJson('/admin/businesses', []);
        $response->assertStatus(403);
    }

    public function test_platform_permission_cannot_authorize_business_action(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $business = Business::factory()->create();

        // Platform admin cannot execute business-scoped operations without membership
        $this->assertFalse($this->authService->can($admin, 'sales.create', $business));
        $this->assertFalse($this->authService->can($admin, 'inventory.manage', $business));
        $this->assertFalse($this->authService->can($admin, 'business.view', $business));
    }

    public function test_business_permission_cannot_authorize_platform_action(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        $this->assertFalse($this->authService->can($owner, 'businesses.create'));
        $this->assertFalse($this->authService->can($owner, 'businesses.suspend'));
    }

    // ==========================================
    // 3. ROLE & PERMISSION GRANULARITY (BUSINESS ROLES)
    // ==========================================

    public function test_owner_allowed_all_business_permissions_and_denied_platform_permissions(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);

        // Positive checks: Owner authority
        $this->assertTrue($this->authService->can($owner, 'business.view', $business));
        $this->assertTrue($this->authService->can($owner, 'business.update', $business));
        $this->assertTrue($this->authService->can($owner, 'staff.manage', $business));
        $this->assertTrue($this->authService->can($owner, 'catalogue.manage', $business));
        $this->assertTrue($this->authService->can($owner, 'finance.manage', $business));
        $this->assertTrue($this->authService->can($owner, 'settings.manage', $business));
        $this->assertTrue($this->authService->can($owner, 'audit.view', $business));

        // Negative check: Denied platform permission
        $this->assertFalse($this->authService->can($owner, 'businesses.create', $business));
    }

    public function test_manager_allowed_manager_permissions_and_denied_owner_exclusive_permissions(): void
    {
        [$manager, $business] = $this->createMemberWithRole('manager');

        // Positive checks: Manager operational permissions
        $this->assertTrue($this->authService->can($manager, 'sales.create', $business));
        $this->assertTrue($this->authService->can($manager, 'invoices.manage', $business));
        $this->assertTrue($this->authService->can($manager, 'customers.manage', $business));
        $this->assertTrue($this->authService->can($manager, 'inventory.manage', $business));
        $this->assertTrue($this->authService->can($manager, 'purchases.manage', $business));
        $this->assertTrue($this->authService->can($manager, 'suppliers.manage', $business));
        $this->assertTrue($this->authService->can($manager, 'reports.view', $business));

        // Negative checks: Manager is denied sensitive finance, payroll, and settings actions by default
        $this->assertFalse($this->authService->can($manager, 'finance.manage', $business));
        $this->assertFalse($this->authService->can($manager, 'expenses.manage', $business));
        $this->assertFalse($this->authService->can($manager, 'settings.manage', $business));
        $this->assertFalse($this->authService->can($manager, 'audit.view', $business));
    }

    public function test_staff_allowed_staff_permissions_and_denied_manager_permissions(): void
    {
        [$staff, $business] = $this->createMemberWithRole('staff');

        // Positive checks: Staff operational permissions
        $this->assertTrue($this->authService->can($staff, 'sales.create', $business));
        $this->assertTrue($this->authService->can($staff, 'customers.manage', $business));
        $this->assertTrue($this->authService->can($staff, 'invoices.view', $business));
        $this->assertTrue($this->authService->can($staff, 'attendance.view_own', $business));

        // Negative checks: Staff cannot manage purchases, suppliers, finance, or settings
        $this->assertFalse($this->authService->can($staff, 'purchases.manage', $business));
        $this->assertFalse($this->authService->can($staff, 'suppliers.manage', $business));
        $this->assertFalse($this->authService->can($staff, 'inventory.manage', $business));
        $this->assertFalse($this->authService->can($staff, 'finance.manage', $business));
    }

    public function test_cashier_allowed_cashier_permissions_and_denied_other_permissions(): void
    {
        [$cashier, $business] = $this->createMemberWithRole('cashier');

        // Positive checks: Cashier permissions
        $this->assertTrue($this->authService->can($cashier, 'sales.create', $business));
        $this->assertTrue($this->authService->can($cashier, 'payments.receive', $business));
        $this->assertTrue($this->authService->can($cashier, 'invoices.view', $business));
        $this->assertTrue($this->authService->can($cashier, 'invoices.print', $business));
        $this->assertTrue($this->authService->can($cashier, 'returns.create', $business));

        // Negative checks: Cashier cannot manage catalogue, purchases, inventory, or staff
        $this->assertFalse($this->authService->can($cashier, 'catalogue.manage', $business));
        $this->assertFalse($this->authService->can($cashier, 'purchases.manage', $business));
        $this->assertFalse($this->authService->can($cashier, 'inventory.manage', $business));
        $this->assertFalse($this->authService->can($cashier, 'staff.manage', $business));
    }

    // ==========================================
    // 4. TENANT ISOLATION & ATTACK SIMULATION
    // ==========================================

    public function test_business_a_user_cannot_access_business_b(): void
    {
        [$userA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create();

        $this->assertTrue($this->authService->canAccessBusiness($userA, $businessA));
        $this->assertFalse($this->authService->canAccessBusiness($userA, $businessB));
        $this->assertFalse($this->authService->can($userA, 'business.view', $businessB));
    }

    public function test_tampering_url_business_id_parameter_fails_to_bypass_tenant_isolation(): void
    {
        [$userA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create();

        $this->actingAs($userA);

        // Even if an attacker supplies a foreign business ID in the query or path
        $response = $this->getJson("/tenant/current?business_id={$businessB->id}");

        $response->assertStatus(200);
        // Server authoritative context resolves strictly to Business A
        $response->assertJson([
            'tenant_id' => $businessA->id,
            'tenant_name' => $businessA->name,
        ]);
        $this->assertNotEquals($businessB->id, $response->json('tenant_id'));
    }

    public function test_tampering_post_business_id_fails_to_bypass_tenant_isolation(): void
    {
        [$userA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create();

        $this->actingAs($userA);

        $response = $this->postJson('/tenant/current', [
            'business_id' => $businessB->id,
            'tenant_id' => $businessB->id,
        ]);

        // Method not allowed on GET route, but server context remains unaffected
        $this->assertNotEquals($businessB->id, $this->tenantContext->id());
    }

    public function test_tampering_tenant_headers_fails_to_bypass_tenant_isolation(): void
    {
        [$userA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create();

        $this->actingAs($userA);

        $response = $this->withHeaders([
            'X-Tenant-ID' => $businessB->id,
            'X-Business-ID' => $businessB->id,
        ])->getJson('/tenant/current');

        $response->assertStatus(200);
        $this->assertSame($businessA->id, $response->json('tenant_id'));
    }

    public function test_assigning_business_a_role_in_business_b_context_fails(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $roleA = Role::create([
            'business_id' => $businessA->id,
            'name' => 'Store A Specialist',
            'slug' => 'store_a_spec_'.Str::random(5),
            'scope' => RoleScope::Business,
        ]);

        $userB = User::factory()->create();
        BusinessMember::create([
            'business_id' => $businessB->id,
            'user_id' => $userB->id,
            'status' => MembershipStatus::Active,
        ]);

        $this->expectException(DomainException::class);

        UserRole::create([
            'user_id' => $userB->id,
            'role_id' => $roleA->id,
            'business_id' => $businessB->id,
        ]);
    }

    public function test_assigning_business_b_role_to_business_a_user_fails(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $userA = User::factory()->create();
        BusinessMember::create([
            'business_id' => $businessA->id,
            'user_id' => $userA->id,
            'status' => MembershipStatus::Active,
        ]);

        $canonicalCashier = Role::where('slug', 'cashier')->whereNull('business_id')->firstOrFail();

        $this->expectException(DomainException::class);

        UserRole::create([
            'user_id' => $userA->id,
            'role_id' => $canonicalCashier->id,
            'business_id' => $businessB->id,
        ]);
    }

    // ==========================================
    // 5. LIFECYCLE STATE CHECKS
    // ==========================================

    public function test_user_in_suspended_business_is_denied_access(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Suspended]);
        [$owner] = $this->createMemberWithRole('owner', $business, isOwner: true);

        $this->assertFalse($this->authService->canAccessBusiness($owner, $business));
        $this->assertFalse($this->authService->can($owner, 'sales.create', $business));

        $this->actingAs($owner);
        $response = $this->getJson('/tenant/current');
        $response->assertStatus(403);
    }

    public function test_user_in_inactive_business_is_denied_access(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Inactive]);
        [$owner] = $this->createMemberWithRole('owner', $business, isOwner: true);

        $this->assertFalse($this->authService->canAccessBusiness($owner, $business));
        $this->assertFalse($this->authService->can($owner, 'sales.create', $business));

        $this->actingAs($owner);
        $response = $this->getJson('/tenant/current');
        $response->assertStatus(403);
    }

    public function test_user_with_suspended_membership_is_denied_access(): void
    {
        [$owner, $business, $member] = $this->createMemberWithRole('owner', isOwner: true);
        $member->update(['status' => MembershipStatus::Suspended]);

        $this->assertFalse($this->authService->canAccessBusiness($owner, $business));
        $this->assertFalse($this->authService->can($owner, 'sales.create', $business));

        $this->actingAs($owner);
        $response = $this->getJson('/tenant/current');
        $response->assertStatus(403);
    }

    public function test_user_with_inactive_membership_is_denied_access(): void
    {
        [$owner, $business, $member] = $this->createMemberWithRole('owner', isOwner: true);
        $member->update(['status' => MembershipStatus::Inactive]);

        $this->assertFalse($this->authService->canAccessBusiness($owner, $business));
        $this->assertFalse($this->authService->can($owner, 'sales.create', $business));

        $this->actingAs($owner);
        $response = $this->getJson('/tenant/current');
        $response->assertStatus(403);
    }

    public function test_inactive_user_is_denied_access_regardless_of_role(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $owner->update(['status' => UserStatus::Inactive]);

        $this->assertFalse($this->authService->canAccessBusiness($owner, $business));
        $this->assertFalse($this->authService->can($owner, 'sales.create', $business));
        $this->assertFalse($owner->hasPermission('sales.create', $business));
    }

    // ==========================================
    // 6. DIRECT ACTION & MIDDLEWARE INVARIANTS
    // ==========================================

    public function test_direct_invocation_of_create_business_without_permission_is_denied(): void
    {
        $ordinaryUser = User::factory()->create();
        $action = app(CreateBusiness::class);

        $this->expectException(AuthorizationException::class);

        $action->execute($ordinaryUser, [
            'name' => 'Direct Invocation Attempt',
            'slug' => 'direct-invocation-attempt',
            'owner_name' => 'Owner',
            'owner_email' => 'owner@attempt.local',
            'owner_password' => 'Pass123!',
        ]);
    }

    public function test_laravel_gate_facade_and_user_can_method_integrate_with_authorization_engine(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        [$staff] = $this->createMemberWithRole('staff', $business);

        $this->tenantContext->set($business);

        // Native Laravel Gate checks
        $this->assertTrue(Gate::forUser($owner)->allows('business.view'));
        $this->assertTrue(Gate::forUser($owner)->allows('settings.manage'));
        $this->assertTrue($owner->can('business.view'));
        $this->assertTrue($owner->hasPermission('business.view'));

        // Staff negative checks via Gate
        $this->assertTrue(Gate::forUser($staff)->allows('sales.create'));
        $this->assertFalse(Gate::forUser($staff)->allows('settings.manage'));
        $this->assertFalse($staff->can('settings.manage'));
        $this->assertTrue($staff->cannot('settings.manage'));
    }

    public function test_route_middleware_cannot_be_bypassed_by_changing_request_parameters(): void
    {
        [$staff, $business] = $this->createMemberWithRole('staff');

        $this->actingAs($staff);

        // Staff has business.view ? Staff has sales.create, but not business.view!
        // /tenant/profile requires permission.business:business.view
        $response = $this->getJson('/tenant/profile?role=owner&override=true');

        $response->assertStatus(403);
    }
}
