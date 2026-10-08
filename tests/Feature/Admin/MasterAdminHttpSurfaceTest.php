<?php

namespace Tests\Feature\Admin;

use App\Actions\Subscription\ProvisionSubscription;
use App\Enums\BillingCycle;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
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

class MasterAdminHttpSurfaceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->platformAdmin = User::factory()->withPlatformRole()->create();
    }

    /**
     * Helper to create a business and member with a canonical role.
     */
    protected function createMemberWithRole(string $roleSlug, bool $isOwner = false): array
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $user = User::factory()->create(['status' => UserStatus::Active, 'is_platform_admin' => false]);

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

        return [$business, $user, $member, $role];
    }

    // ==================================================
    // 1. AUTHORIZATION TESTS
    // ==================================================

    public function test_guest_cannot_access_admin_overview_and_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');

        $responseOverview = $this->get('/admin/overview');
        $responseOverview->assertRedirect('/login');
    }

    public function test_guest_cannot_access_businesses_list(): void
    {
        $response = $this->get('/admin/businesses');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_create_business_page(): void
    {
        $response = $this->get('/admin/businesses/create');
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_subscription_plans(): void
    {
        $response = $this->get('/admin/subscription-plans');
        $response->assertRedirect('/login');
    }

    public function test_business_owner_cannot_access_admin_surface(): void
    {
        [, $owner] = $this->createMemberWithRole('owner', isOwner: true);

        $this->actingAs($owner);

        $response = $this->get('/admin');
        $response->assertStatus(403);

        $responseBusinesses = $this->get('/admin/businesses');
        $responseBusinesses->assertStatus(403);
    }

    public function test_manager_cannot_access_admin_surface(): void
    {
        [, $manager] = $this->createMemberWithRole('manager');

        $this->actingAs($manager);

        $response = $this->get('/admin');
        $response->assertStatus(403);
    }

    public function test_staff_cannot_access_admin_surface(): void
    {
        [, $staff] = $this->createMemberWithRole('staff');

        $this->actingAs($staff);

        $response = $this->get('/admin');
        $response->assertStatus(403);
    }

    public function test_cashier_cannot_access_admin_surface(): void
    {
        [, $cashier] = $this->createMemberWithRole('cashier');

        $this->actingAs($cashier);

        $response = $this->get('/admin');
        $response->assertStatus(403);
    }

    public function test_platform_admin_can_access_admin_overview_with_truthful_metrics(): void
    {
        $this->actingAs($this->platformAdmin);

        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('System Overview');
        $response->assertSee('Active Tenants');
        $response->assertSee('Platform Users', false);
        $response->assertSee('Total Revenue');
        $response->assertSee('Not available yet'); // Truthful state for future modules
    }

    // ==================================================
    // 2. BUSINESS / TENANT MANAGEMENT TESTS
    // ==================================================

    public function test_admin_can_view_businesses_index_page(): void
    {
        $business = Business::factory()->create(['name' => 'Solaris Retail Hub']);

        $this->actingAs($this->platformAdmin);

        $response = $this->get('/admin/businesses');

        $response->assertOk();
        $response->assertViewIs('admin.businesses.index');
        $response->assertSee('Solaris Retail Hub');
    }

    public function test_admin_can_search_and_filter_businesses(): void
    {
        Business::factory()->create(['name' => 'Searchable Alpha Inc', 'slug' => 'searchable-alpha']);
        Business::factory()->create(['name' => 'Ignored Beta Corp', 'slug' => 'ignored-beta']);

        $this->actingAs($this->platformAdmin);

        $response = $this->get('/admin/businesses?search=Searchable');

        $response->assertOk();
        $response->assertSee('Searchable Alpha Inc');
        $response->assertDontSee('Ignored Beta Corp');
    }

    public function test_admin_can_view_create_business_page(): void
    {
        $plan = SubscriptionPlan::factory()->create(['name' => 'Standard Retail Plan', 'is_active' => true]);

        $this->actingAs($this->platformAdmin);

        $response = $this->get('/admin/businesses/create');

        $response->assertOk();
        $response->assertViewIs('admin.businesses.create');
        $response->assertSee('Provision Business Tenant');
        $response->assertSee('Standard Retail Plan');
    }

    public function test_admin_can_provision_business_via_web_form_without_plan(): void
    {
        $this->actingAs($this->platformAdmin);

        $payload = [
            'name' => 'Nexus Hardware Supplies',
            'legal_name' => 'Nexus Hardware Supplies Private Limited',
            'slug' => 'nexus-hardware',
            'business_type' => 'retail',
            'email' => 'contact@nexushardware.local',
            'phone' => '+919876543299',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411001',
            'country' => 'IN',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'tax_number' => '27ABCDE1234F1Z8',
            'owner_name' => 'Sanjay Patil',
            'owner_email' => 'sanjay@nexushardware.local',
            'owner_phone' => '+919876543299',
            'owner_password' => 'Password123!',
        ];

        $response = $this->post('/admin/businesses', $payload);

        $business = Business::where('slug', 'nexus-hardware')->first();
        $this->assertNotNull($business);

        $response->assertRedirect(route('admin.businesses.show', $business));
        $response->assertSessionHas('success');

        // Verify owner is created and is not a platform admin
        $owner = User::where('email', 'sanjay@nexushardware.local')->first();
        $this->assertNotNull($owner);
        $this->assertFalse($owner->is_platform_admin);

        // Verify Master Admin is NEVER added as a business member
        $this->assertFalse(BusinessMember::where('business_id', $business->id)->where('user_id', $this->platformAdmin->id)->exists());
    }

    public function test_admin_can_provision_business_with_initial_subscription_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Enterprise Growth',
            'price' => 1999.00,
            'billing_cycle' => BillingCycle::Monthly,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        $this->actingAs($this->platformAdmin);

        $payload = [
            'name' => 'Zenith Tech Logistics',
            'slug' => 'zenith-logistics',
            'business_type' => 'wholesale',
            'owner_name' => 'Anil Verma',
            'owner_email' => 'anil@zenithlogistics.local',
            'owner_password' => 'Password123!',
            'plan_id' => $plan->id,
            'skip_trial' => false,
        ];

        $response = $this->post('/admin/businesses', $payload);

        $business = Business::where('slug', 'zenith-logistics')->first();
        $this->assertNotNull($business);

        $response->assertRedirect(route('admin.businesses.show', $business));

        // 1. Business exists and is active
        $this->assertEquals(BusinessStatus::Active, $business->status);

        // 2. Owner exists and is linked
        $owner = User::where('email', 'anil@zenithlogistics.local')->first();
        $this->assertNotNull($owner);
        $this->assertFalse($owner->is_platform_admin);
        $this->assertTrue($business->members()->where('user_id', $owner->id)->where('is_owner', true)->exists());

        // 3. Owner role exists
        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->first();
        $this->assertNotNull($ownerRole);
        $this->assertTrue(UserRole::where('business_id', $business->id)->where('user_id', $owner->id)->where('role_id', $ownerRole->id)->exists());

        // 4. Subscription exists with correct plan, trial dates and status
        $sub = $business->activeSubscription;
        $this->assertNotNull($sub);
        $this->assertEquals($plan->id, $sub->plan_id);
        $this->assertEquals(SubscriptionStatus::Trial, $sub->status);
        $this->assertNotNull($sub->starts_at);
        $this->assertNotNull($sub->trial_ends_at);
        $this->assertNotNull($sub->ends_at);
    }

    public function test_business_and_initial_subscription_provisioning_is_atomic(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Atomic Enterprise Plan',
            'is_active' => true,
        ]);

        $this->actingAs($this->platformAdmin);

        $initialBusinessCount = Business::count();
        $initialUserCount = User::count();
        $initialMembershipCount = BusinessMember::count();
        $initialUserRoleCount = UserRole::count();
        $initialSubscriptionCount = Subscription::count();
        $initialAuditLogCount = AuditLog::count();

        // Force ProvisionSubscription to fail during provisioning
        $this->partialMock(ProvisionSubscription::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new \DomainException('Forced failure during subscription provisioning for atomicity verification.'));
        });

        $payload = [
            'name' => 'Atomic Rollback Corp',
            'slug' => 'atomic-rollback',
            'business_type' => 'retail',
            'owner_name' => 'Failed Owner',
            'owner_email' => 'failedowner@atomic.local',
            'owner_password' => 'Password123!',
            'plan_id' => $plan->id,
            'skip_trial' => false,
        ];

        // Attempt creation via HTTP POST
        $response = $this->from(route('admin.businesses.create'))
            ->post(route('admin.businesses.store'), $payload);

        $response->assertRedirect(route('admin.businesses.create'));
        $response->assertSessionHas('error', 'Forced failure during subscription provisioning for atomicity verification.');

        // Verify full atomicity: all database counts must be strictly unchanged
        $this->assertSame($initialBusinessCount, Business::count(), 'Business record was not rolled back.');
        $this->assertSame($initialUserCount, User::count(), 'Owner user record was not rolled back.');
        $this->assertSame($initialMembershipCount, BusinessMember::count(), 'Business membership was not rolled back.');
        $this->assertSame($initialUserRoleCount, UserRole::count(), 'User role assignment was not rolled back.');
        $this->assertSame($initialSubscriptionCount, Subscription::count(), 'Subscription record was not rolled back.');
        $this->assertSame($initialAuditLogCount, AuditLog::count(), 'Audit log was not rolled back.');

        // Specific entity absence assertions
        $this->assertFalse(Business::where('slug', 'atomic-rollback')->exists());
        $this->assertFalse(User::where('email', 'failedowner@atomic.local')->exists());
        $this->assertFalse(AuditLog::where('payload->slug', 'atomic-rollback')->exists());
    }

    public function test_business_and_initial_subscription_provisioning_is_atomic_via_json_request(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($this->platformAdmin);

        $initialBusinessCount = Business::count();
        $initialUserCount = User::count();

        $this->partialMock(ProvisionSubscription::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new \DomainException('Subscription provisioning failed'));
        });

        $response = $this->postJson(route('admin.businesses.store'), [
            'name' => 'Atomic Json Corp',
            'slug' => 'atomic-json',
            'owner_name' => 'Json Owner',
            'owner_email' => 'jsonowner@atomic.local',
            'owner_password' => 'Password123!',
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Subscription provisioning failed',
        ]);

        $this->assertSame($initialBusinessCount, Business::count());
        $this->assertSame($initialUserCount, User::count());
        $this->assertFalse(Business::where('slug', 'atomic-json')->exists());
    }

    public function test_business_creation_rejects_invalid_data(): void
    {
        $this->actingAs($this->platformAdmin);

        $response = $this->post('/admin/businesses', [
            'name' => '', // missing name
            'slug' => 'invalid slug!', // invalid regex
            'owner_email' => 'not-an-email',
            'owner_password' => 'short', // less than 8 chars
        ]);

        $response->assertSessionHasErrors(['name', 'slug', 'owner_name', 'owner_email', 'owner_password']);
    }

    public function test_business_creation_rejects_duplicate_slug(): void
    {
        Business::factory()->create(['slug' => 'duplicate-slug']);

        $this->actingAs($this->platformAdmin);

        $response = $this->post('/admin/businesses', [
            'name' => 'Another Store',
            'slug' => 'duplicate-slug',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@another.local',
            'owner_password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_admin_can_view_business_details(): void
    {
        [$business, $owner] = $this->createMemberWithRole('owner', isOwner: true);

        $this->actingAs($this->platformAdmin);

        $response = $this->get(route('admin.businesses.show', $business));

        $response->assertOk();
        $response->assertViewIs('admin.businesses.show');
        $response->assertSee($business->name);
        $response->assertSee($owner->name);
        $response->assertSee($owner->email);
    }

    // ==================================================
    // 3. SUBSCRIPTION PLANS TESTS
    // ==================================================

    public function test_admin_can_view_subscription_plans_index(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Pro Cloud Tier',
            'price' => 1499.00,
        ]);

        $this->actingAs($this->platformAdmin);

        $response = $this->get('/admin/subscription-plans');

        $response->assertOk();
        $response->assertViewIs('admin.plans.index');
        $response->assertSee('Pro Cloud Tier');
    }

    public function test_admin_can_view_create_subscription_plan_page(): void
    {
        $this->actingAs($this->platformAdmin);

        $response = $this->get('/admin/subscription-plans/create');

        $response->assertOk();
        $response->assertViewIs('admin.plans.create');
        $response->assertSee('Create Subscription Plan');
    }

    public function test_admin_can_create_subscription_plan_via_web_form(): void
    {
        $this->actingAs($this->platformAdmin);

        $payload = [
            'name' => 'Startup Starter Plan',
            'slug' => 'startup-starter',
            'price' => '499.00',
            'billing_cycle' => 'monthly',
            'trial_days' => 7,
            'description' => 'Ideal for single-counter stores',
            'features' => "1 POS Register\nDaily Sales Report\nEmail Support",
            'is_active' => '1',
        ];

        $response = $this->post('/admin/subscription-plans', $payload);

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionHas('success');

        $plan = SubscriptionPlan::where('slug', 'startup-starter')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(499.00, (float) $plan->price);
        $this->assertEquals(BillingCycle::Monthly, $plan->billing_cycle);
        $this->assertCount(3, $plan->features);
    }

    public function test_admin_can_view_edit_plan_page(): void
    {
        $plan = SubscriptionPlan::factory()->create(['name' => 'Editable Plan']);

        $this->actingAs($this->platformAdmin);

        $response = $this->get(route('admin.subscription-plans.edit', $plan));

        $response->assertOk();
        $response->assertViewIs('admin.plans.edit');
        $response->assertSee('Editable Plan');
    }

    public function test_admin_can_update_subscription_plan_via_web_form(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Old Plan Name',
            'price' => 800.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->platformAdmin);

        $payload = [
            'name' => 'Upgraded Plan Name',
            'price' => '1200.00',
            'billing_cycle' => 'quarterly',
            'trial_days' => 0,
            'is_active' => '0',
        ];

        $response = $this->put(route('admin.subscription-plans.update', $plan), $payload);

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $response->assertSessionHas('success');

        $plan->refresh();
        $this->assertEquals('Upgraded Plan Name', $plan->name);
        $this->assertEquals(1200.00, (float) $plan->price);
        $this->assertEquals(BillingCycle::Quarterly, $plan->billing_cycle);
        $this->assertFalse($plan->is_active);
    }

    public function test_ordinary_user_cannot_mutate_subscription_plans(): void
    {
        [, $owner] = $this->createMemberWithRole('owner', isOwner: true);
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($owner);

        $responseCreate = $this->post('/admin/subscription-plans', [
            'name' => 'Malicious Plan',
            'price' => '100.00',
            'billing_cycle' => 'monthly',
        ]);
        $responseCreate->assertStatus(403);

        $responseUpdate = $this->put(route('admin.subscription-plans.update', $plan), [
            'name' => 'Hacked Plan',
            'price' => '1.00',
            'billing_cycle' => 'monthly',
        ]);
        $responseUpdate->assertStatus(403);
    }

    // ==================================================
    // 4. SUBSCRIPTION LIFECYCLE & STATE MACHINE TESTS
    // ==================================================

    public function test_admin_can_assign_subscription_via_web_request(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $this->actingAs($this->platformAdmin);

        $response = $this->post('/admin/subscriptions', [
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'skip_trial' => true,
        ]);

        $response->assertRedirect(route('admin.businesses.show', $business));
        $response->assertSessionHas('success');

        $sub = $business->activeSubscription;
        $this->assertNotNull($sub);
        $this->assertEquals(SubscriptionStatus::Active, $sub->status);
    }

    public function test_admin_can_transition_subscription_via_web_request(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);
        $subscription = Subscription::factory()->create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
        ]);

        $this->actingAs($this->platformAdmin);

        // Transition Trial -> Active
        $response = $this->post(route('admin.subscriptions.transition', $subscription), [
            'status' => 'active',
            'reason' => 'Customer completed offline payment',
        ]);

        $response->assertRedirect(route('admin.businesses.show', $business));
        $response->assertSessionHas('success');

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Active, $subscription->status);
    }

    public function test_invalid_subscription_state_transition_is_rejected(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);
        $subscription = Subscription::factory()->create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Cancelled, // Terminal state
        ]);

        $this->actingAs($this->platformAdmin);

        // JSON endpoint returns 422 with domain error message
        $responseJson = $this->postJson(route('admin.subscriptions.transition', $subscription), [
            'status' => 'active',
        ]);
        $responseJson->assertStatus(422);

        // Web form submission redirects back with error flash
        $responseWeb = $this->from(route('admin.businesses.show', $business))
            ->post(route('admin.subscriptions.transition', $subscription), [
                'status' => 'active',
            ]);
        $responseWeb->assertRedirect(route('admin.businesses.show', $business));
        $responseWeb->assertSessionHas('error');
    }
}
