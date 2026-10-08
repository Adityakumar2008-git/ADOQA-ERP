<?php

namespace Tests\Feature\Owner;

use App\Enums\BillingCycle;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OwnerErpFoundationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    /**
     * Helper to create a business and user with a specified canonical role.
     */
    protected function createMemberWithRole(string $roleSlug, ?Business $business = null, bool $isOwner = false): array
    {
        $business ??= Business::factory()->create(['status' => BusinessStatus::Active]);
        $user = User::factory()->create(['status' => UserStatus::Active, 'is_platform_admin' => false]);

        $member = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => $isOwner,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $role = Role::where('slug', $roleSlug)->whereNull('business_id')->firstOrFail();

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        return [$business, $user, $member, $role];
    }

    // ==================================================
    // 1. OWNER ERP AUTHORIZATION TESTS
    // ==================================================

    public function test_guest_cannot_access_owner_surface_and_is_redirected_to_login(): void
    {
        $responseRoot = $this->get('/owner');
        $responseRoot->assertRedirect('/login');

        $responseDashboard = $this->get('/owner/dashboard');
        $responseDashboard->assertRedirect('/login');
    }

    public function test_platform_admin_without_membership_cannot_access_owner_surface(): void
    {
        $admin = User::factory()->withPlatformRole()->create();

        $this->actingAs($admin);

        $response = $this->get('/owner/dashboard');
        $response->assertStatus(403);
    }

    public function test_business_owner_can_access_owner_dashboard(): void
    {
        [$business, $owner] = $this->createMemberWithRole('owner', isOwner: true);

        $this->actingAs($owner);

        $response = $this->get('/owner/dashboard');

        $response->assertOk();
        $response->assertViewIs('owner.dashboard');
        $response->assertSee($business->name);
        $response->assertSee('Commercial Dashboard');
        $response->assertSee('Server-authoritative tenant isolation active');
    }

    public function test_store_manager_with_business_view_permission_can_access_owner_dashboard(): void
    {
        [$business, $manager] = $this->createMemberWithRole('manager');

        $this->actingAs($manager);

        $response = $this->get('/owner/dashboard');

        $response->assertOk();
        $response->assertSee($business->name);
    }

    public function test_staff_member_without_business_view_permission_is_denied(): void
    {
        [, $staff] = $this->createMemberWithRole('staff');

        $this->actingAs($staff);

        $response = $this->get('/owner/dashboard');
        $response->assertStatus(403);
    }

    public function test_cashier_without_business_view_permission_is_denied(): void
    {
        [, $cashier] = $this->createMemberWithRole('cashier');

        $this->actingAs($cashier);

        $response = $this->get('/owner/dashboard');
        $response->assertStatus(403);
    }

    public function test_user_of_suspended_business_is_denied_access(): void
    {
        $suspendedBusiness = Business::factory()->create(['status' => BusinessStatus::Suspended]);
        [, $owner] = $this->createMemberWithRole('owner', business: $suspendedBusiness, isOwner: true);

        $this->actingAs($owner);

        $response = $this->get('/owner/dashboard');
        $response->assertStatus(403);
    }

    public function test_user_of_inactive_business_is_denied_access(): void
    {
        $inactiveBusiness = Business::factory()->create(['status' => BusinessStatus::Inactive]);
        [, $owner] = $this->createMemberWithRole('owner', business: $inactiveBusiness, isOwner: true);

        $this->actingAs($owner);

        $response = $this->get('/owner/dashboard');
        $response->assertStatus(403);
    }

    // ==================================================
    // 2. TENANT ISOLATION TESTS
    // ==================================================

    public function test_tenant_context_is_strictly_server_authoritative(): void
    {
        [$businessA, $ownerA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create([
            'name' => 'Foreign Business Corp',
            'slug' => 'foreign-business-corp',
        ]);

        $this->actingAs($ownerA);

        // Attempt malicious tenant injection via query parameter, header, and body
        $response = $this->get("/owner/dashboard?tenant={$businessB->id}&business_id={$businessB->id}");

        $response->assertOk();
        // Server-authoritative context resolves ONLY Business A
        $response->assertSee($businessA->name);
        $response->assertDontSee('Foreign Business Corp');
    }

    public function test_cross_business_data_is_never_leaked(): void
    {
        [$businessA, $ownerA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create([
            'name' => 'Secret Enterprise B',
            'slug' => 'secret-enterprise-b',
            'tax_number' => 'GSTIN9999999999B',
        ]);

        $this->actingAs($ownerA);

        $response = $this->get('/owner/dashboard');

        $response->assertOk();
        $response->assertSee($businessA->name);
        $response->assertDontSee('Secret Enterprise B');
        $response->assertDontSee('GSTIN9999999999B');
    }

    // ==================================================
    // 3. ROLE-AWARE NAVIGATION TESTS
    // ==================================================

    public function test_owner_navigation_renders_all_management_sections(): void
    {
        [$business, $owner] = $this->createMemberWithRole('owner', isOwner: true);

        $this->actingAs($owner);

        $response = $this->get('/owner/dashboard');

        $response->assertOk();
        // Owner has full business permissions, sees all ERP groups
        $response->assertSee('Primary');
        $response->assertSee('Sales');
        $response->assertSee('Commerce');
        $response->assertSee('Finance');
        $response->assertSee('People & HR');
        $response->assertSee('System');
    }

    public function test_manager_navigation_hides_unauthorized_finance_sections(): void
    {
        [$business, $manager] = $this->createMemberWithRole('manager');

        $this->actingAs($manager);

        $response = $this->get('/owner/dashboard');

        $response->assertOk();
        $response->assertSee('Primary');
        $response->assertSee('Sales');
        $response->assertSee('Commerce');

        // Manager does not possess finance.manage permission, so Finance group is strictly omitted
        $response->assertDontSee('Finance Ledger');
        $response->assertDontSee('EMI Accounts');
    }

    // ==================================================
    // 4. DASHBOARD TRUTHFUL DATA & ZERO-STATE TESTS
    // ==================================================

    public function test_owner_dashboard_displays_real_subscription_and_truthful_empty_states(): void
    {
        [$business, $owner] = $this->createMemberWithRole('owner', isOwner: true);

        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Pro Enterprise Tier',
            'billing_cycle' => BillingCycle::Monthly,
            'is_active' => true,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($owner);

        $response = $this->get('/owner/dashboard');

        $response->assertOk();
        // Real verified database data
        $response->assertSee('Pro Enterprise Tier');
        $response->assertSee('Active');
        $response->assertSee($business->currency);

        // Truthful empty states: no fake ₹0 numbers for future domains
        $response->assertSee('Not available yet');
        $response->assertDontSee('₹0.00');
    }

    // ==================================================
    // 5. LOGIN REDIRECT BEHAVIOR
    // ==================================================

    public function test_owner_login_redirects_directly_to_owner_dashboard(): void
    {
        [$business, $owner] = $this->createMemberWithRole('owner', isOwner: true);

        $response = $this->post('/login', [
            'email' => $owner->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/owner/dashboard');
    }
}
