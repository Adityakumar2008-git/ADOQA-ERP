<?php

namespace Tests\Feature\Business;

use App\Actions\Business\ProvisionBusinessWithSubscription;
use App\Actions\Subscription\ProvisionSubscription;
use App\Enums\BillingCycle;
use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProvisionBusinessWithSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->platformAdmin = User::factory()->withPlatformRole()->create();
    }

    public function test_business_and_initial_subscription_provisioning_is_atomic_at_action_level(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Gold Commercial',
            'is_active' => true,
        ]);

        $initialBusinessCount = Business::count();
        $initialUserCount = User::count();
        $initialMembershipCount = BusinessMember::count();
        $initialUserRoleCount = UserRole::count();
        $initialSubscriptionCount = Subscription::count();
        $initialAuditLogCount = AuditLog::count();

        // Partial mock ProvisionSubscription to throw an exception
        $this->partialMock(ProvisionSubscription::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andThrow(new DomainException('Simulated action failure during subscription provisioning'));
        });

        $orchestrator = app(ProvisionBusinessWithSubscription::class);

        $payload = [
            'name' => 'Atomic Fail Test Co',
            'slug' => 'atomic-fail-test',
            'business_type' => 'retail',
            'owner_name' => 'Failed Owner',
            'owner_email' => 'failed@atomicfail.local',
            'owner_password' => 'Password123!',
        ];

        try {
            $orchestrator->execute(
                actor: $this->platformAdmin,
                businessData: $payload,
                plan: $plan
            );
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $e) {
            $this->assertSame('Simulated action failure during subscription provisioning', $e->getMessage());
        }

        // Verify that NO partial state was persisted
        $this->assertSame($initialBusinessCount, Business::count(), 'Business record was not rolled back.');
        $this->assertSame($initialUserCount, User::count(), 'Owner user record was not rolled back.');
        $this->assertSame($initialMembershipCount, BusinessMember::count(), 'Business membership was not rolled back.');
        $this->assertSame($initialUserRoleCount, UserRole::count(), 'User role assignment was not rolled back.');
        $this->assertSame($initialSubscriptionCount, Subscription::count(), 'Subscription record was not rolled back.');
        $this->assertSame($initialAuditLogCount, AuditLog::count(), 'Audit log was not rolled back.');

        $this->assertFalse(Business::where('slug', 'atomic-fail-test')->exists());
        $this->assertFalse(User::where('email', 'failed@atomicfail.local')->exists());
    }

    public function test_successful_combined_provisioning_at_action_level(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Silver Commercial',
            'price' => 799.00,
            'billing_cycle' => BillingCycle::Monthly,
            'trial_days' => 7,
            'is_active' => true,
        ]);

        $orchestrator = app(ProvisionBusinessWithSubscription::class);

        $payload = [
            'name' => 'Silver Peak Trading',
            'slug' => 'silver-peak',
            'business_type' => 'retail',
            'owner_name' => 'Vikram Seth',
            'owner_email' => 'vikram@silverpeak.local',
            'owner_password' => 'Password123!',
        ];

        $business = $orchestrator->execute(
            actor: $this->platformAdmin,
            businessData: $payload,
            plan: $plan,
            subscriptionOptions: ['skip_trial' => false]
        );

        // 1. Business exists and active
        $this->assertInstanceOf(Business::class, $business);
        $this->assertSame(BusinessStatus::Active, $business->status);
        $this->assertSame('silver-peak', $business->slug);

        // 2. Owner user exists and is not platform admin
        $owner = User::where('email', 'vikram@silverpeak.local')->first();
        $this->assertNotNull($owner);
        $this->assertFalse($owner->is_platform_admin);

        // 3. Owner membership exists
        $this->assertTrue(BusinessMember::where('business_id', $business->id)->where('user_id', $owner->id)->where('is_owner', true)->exists());

        // 4. Owner role assigned
        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->first();
        $this->assertNotNull($ownerRole);
        $this->assertTrue(UserRole::where('business_id', $business->id)->where('user_id', $owner->id)->where('role_id', $ownerRole->id)->exists());

        // 5. Subscription provisioned with correct plan and dates
        $sub = $business->activeSubscription;
        $this->assertNotNull($sub);
        $this->assertSame($plan->id, $sub->plan_id);
        $this->assertSame(SubscriptionStatus::Trial, $sub->status);
        $this->assertNotNull($sub->starts_at);
        $this->assertNotNull($sub->trial_ends_at);
        $this->assertNotNull($sub->ends_at);

        // 6. Audit logs recorded for both business and subscription
        $this->assertTrue(AuditLog::where('business_id', $business->id)->where('action', 'business.provisioned')->exists());
        $this->assertTrue(AuditLog::where('business_id', $business->id)->where('action', 'subscription.provisioned')->exists());
    }
}
