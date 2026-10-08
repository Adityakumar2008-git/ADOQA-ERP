<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\CreateSubscriptionPlan;
use App\Actions\Subscription\ProvisionSubscription;
use App\Actions\Subscription\TransitionSubscriptionStatus;
use App\Actions\Subscription\UpdateSubscriptionPlan;
use App\Enums\BillingCycle;
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
use App\Services\Subscription\SubscriptionService;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SubscriptionDomainTest extends TestCase
{
    use DatabaseTransactions;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->subscriptionService = app(SubscriptionService::class);
    }

    /**
     * Helper to provision a business and user with a specific role.
     */
    protected function createMemberWithRole(string $roleSlug, ?Business $business = null, bool $isOwner = false): array
    {
        $business ??= Business::factory()->create();
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
    // 1. PLAN MANAGEMENT (PLATFORM SCOPE)
    // ==========================================

    public function test_platform_admin_can_create_and_update_subscription_plan(): void
    {
        $admin = User::factory()->withPlatformRole()->create();

        $createAction = app(CreateSubscriptionPlan::class);
        $plan = $createAction->execute($admin, [
            'name' => 'Growth Plan',
            'slug' => 'growth-plan',
            'price' => '2499.00',
            'currency' => 'INR',
            'billing_cycle' => BillingCycle::Monthly,
            'trial_days' => 14,
            'features' => ['multi_store' => true, 'barcode' => true],
        ]);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'slug' => 'growth-plan',
            'price' => 2499.00,
            'trial_days' => 14,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription_plan.created',
            'auditable_type' => SubscriptionPlan::class,
            'auditable_id' => $plan->id,
        ]);

        $updateAction = app(UpdateSubscriptionPlan::class);
        $updateAction->execute($admin, $plan, [
            'price' => '2999.00',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'price' => 2999.00,
        ]);
    }

    public function test_ordinary_business_user_cannot_create_subscription_plan(): void
    {
        [$owner] = $this->createMemberWithRole('owner', isOwner: true);
        $action = app(CreateSubscriptionPlan::class);

        $this->expectException(AuthorizationException::class);

        $action->execute($owner, [
            'name' => 'Hacked Plan',
            'price' => '0.00',
            'billing_cycle' => 'monthly',
        ]);
    }

    // ==========================================
    // 2. SUBSCRIPTION PROVISIONING
    // ==========================================

    public function test_platform_admin_can_provision_subscription_with_trial(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $business = Business::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'trial_days' => 14,
            'billing_cycle' => BillingCycle::Monthly,
        ]);

        $action = app(ProvisionSubscription::class);
        $subscription = $action->execute($admin, $business, $plan);

        $this->assertSame(SubscriptionStatus::Trial, $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertSame($business->id, $subscription->business_id);
        $this->assertSame($plan->id, $subscription->plan_id);

        $this->assertTrue($this->subscriptionService->hasActiveSubscription($business));
        $this->assertSame($subscription->id, $this->subscriptionService->getActiveSubscription($business)->id);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'action' => 'subscription.provisioned',
            'auditable_type' => Subscription::class,
            'auditable_id' => $subscription->id,
        ]);
    }

    public function test_platform_admin_can_provision_active_subscription_without_trial(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $business = Business::factory()->create();
        $plan = SubscriptionPlan::factory()->withoutTrial()->create([
            'billing_cycle' => BillingCycle::Yearly,
        ]);

        $action = app(ProvisionSubscription::class);
        $subscription = $action->execute($admin, $business, $plan);

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_duplicate_active_subscription_is_blocked_at_domain_and_database_level(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $business = Business::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $action = app(ProvisionSubscription::class);
        $action->execute($admin, $business, $plan);

        // Second provisioning attempt via Domain Action must throw DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already has an active, trial, or past-due subscription/');

        $action->execute($admin, $business, $plan);
    }

    public function test_duplicate_active_subscription_prevented_by_postgresql_partial_unique_index(): void
    {
        $business = Business::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        // Raw database insert bypassing domain layer
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    // ==========================================
    // 3. STATE MACHINE TRANSITIONS
    // ==========================================

    public function test_valid_lifecycle_transitions_from_trial_to_active_and_past_due(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $subscription = Subscription::factory()->trial()->create();

        $action = app(TransitionSubscriptionStatus::class);

        // Trial -> Active
        $subscription = $action->execute($admin, $subscription, SubscriptionStatus::Active);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);

        // Active -> Past Due
        $subscription = $action->execute($admin, $subscription, SubscriptionStatus::PastDue);
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);

        // Past Due -> Suspended
        $subscription = $action->execute($admin, $subscription, SubscriptionStatus::Suspended);
        $this->assertSame(SubscriptionStatus::Suspended, $subscription->status);

        // Suspended -> Active (Cured)
        $subscription = $action->execute($admin, $subscription, SubscriptionStatus::Active);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
    }

    public function test_invalid_lifecycle_transition_is_rejected(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $subscription = Subscription::factory()->trial()->create();

        $action = app(TransitionSubscriptionStatus::class);

        // Trial cannot directly jump to Suspended or PastDue
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Invalid subscription transition from \[trial\] to \[suspended\]/');

        $action->execute($admin, $subscription, SubscriptionStatus::Suspended);
    }

    public function test_terminal_states_reject_any_further_transitions(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $subscription = Subscription::factory()->cancelled()->create();

        $action = app(TransitionSubscriptionStatus::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Invalid subscription transition from \[cancelled\] to \[active\]/');

        $action->execute($admin, $subscription, SubscriptionStatus::Active);
    }

    public function test_business_owner_can_cancel_their_own_subscription(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $subscription = Subscription::factory()->create([
            'business_id' => $business->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->actingAs($owner);

        $response = $this->postJson('/tenant/subscription/cancel', [
            'reason' => 'Too expensive for current stage',
        ]);

        $response->assertStatus(200);
        $this->assertSame('cancelled', $subscription->fresh()->status->value);
        $this->assertNotNull($subscription->fresh()->cancelled_at);

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'action' => 'subscription.status_transitioned',
        ]);
    }

    public function test_business_owner_cannot_transition_to_arbitrary_states(): void
    {
        [$owner, $business] = $this->createMemberWithRole('owner', isOwner: true);
        $subscription = Subscription::factory()->create([
            'business_id' => $business->id,
            'status' => SubscriptionStatus::PastDue,
        ]);

        $action = app(TransitionSubscriptionStatus::class);

        // Owner cannot cure/reactivate past-due without platform admin or billing engine
        $this->expectException(AuthorizationException::class);

        $action->execute($owner, $subscription, SubscriptionStatus::Active);
    }

    // ==========================================
    // 4. TENANT ISOLATION & SECURITY NEGATIVE TESTS
    // ==========================================

    public function test_unauthenticated_user_cannot_access_subscription_endpoints(): void
    {
        $response1 = $this->getJson('/admin/subscription-plans');
        $response1->assertStatus(401);

        $response2 = $this->getJson('/tenant/subscription');
        $response2->assertStatus(401);
    }

    public function test_ordinary_business_user_cannot_view_another_business_subscription(): void
    {
        [$ownerA, $businessA] = $this->createMemberWithRole('owner', isOwner: true);
        $businessB = Business::factory()->create();
        Subscription::factory()->create([
            'business_id' => $businessB->id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->actingAs($ownerA);

        // Server authoritative context serves only business A
        $response = $this->getJson('/tenant/subscription?business_id='.$businessB->id);
        $response->assertStatus(200);

        // Business A has no subscription provisioned yet
        $this->assertFalse($response->json('has_active_subscription'));
    }

    public function test_cannot_provision_subscription_for_suspended_or_inactive_business(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $suspendedBiz = Business::factory()->create(['status' => 'suspended']);
        $plan = SubscriptionPlan::factory()->create();

        $action = app(ProvisionSubscription::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot provision subscription for an inactive or suspended business/');

        $action->execute($admin, $suspendedBiz, $plan);
    }

    public function test_cannot_provision_inactive_subscription_plan(): void
    {
        $admin = User::factory()->withPlatformRole()->create();
        $business = Business::factory()->create();
        $inactivePlan = SubscriptionPlan::factory()->inactive()->create();

        $action = app(ProvisionSubscription::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot provision an inactive subscription plan/');

        $action->execute($admin, $business, $inactivePlan);
    }
}
