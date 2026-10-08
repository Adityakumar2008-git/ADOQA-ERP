<?php

namespace App\Actions\Subscription;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ProvisionSubscription
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Provision a subscription plan for a business tenant.
     * Can only be executed by platform administrators with 'subscriptions.manage'.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, SubscriptionPlan $plan, array $options = []): Subscription
    {
        // 1. Authorization: Platform scope check
        if ($this->authorizationService->cannot($actor, 'subscriptions.manage')) {
            throw new AuthorizationException('Unauthorized: Only platform administrators can provision subscriptions.');
        }

        // 2. Business validity check
        if (! $business->isActive()) {
            throw new DomainException("Cannot provision subscription for an inactive or suspended business [{$business->id}].");
        }

        // 3. Plan validity check
        if (! $plan->is_active) {
            throw new DomainException("Cannot provision an inactive subscription plan [{$plan->name}].");
        }

        // 4. Duplicate active subscription check
        if ($business->activeSubscription()->exists()) {
            throw new DomainException("Business [{$business->name}] already has an active, trial, or past-due subscription. Cancel or expire it before provisioning a new one.");
        }

        return DB::transaction(function () use ($actor, $business, $plan, $options) {
            $startsAt = now();
            $skipTrial = $options['skip_trial'] ?? false;

            if ($plan->trial_days > 0 && ! $skipTrial) {
                $status = SubscriptionStatus::Trial;
                $trialEndsAt = $startsAt->copy()->addDays($plan->trial_days);
                $endsAt = $trialEndsAt->copy();
            } else {
                $status = SubscriptionStatus::Active;
                $trialEndsAt = null;
                $endsAt = $this->calculatePeriodEnd($startsAt, $plan->billing_cycle);
            }

            $subscription = Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => $trialEndsAt,
                'cancelled_at' => null,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'subscription.provisioned',
                'auditable_type' => Subscription::class,
                'auditable_id' => $subscription->id,
                'payload' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'status' => $subscription->status->value,
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt?->toIso8601String(),
                ],
            ]);

            return $subscription->load(['business', 'plan']);
        });
    }

    /**
     * Compute end of billing period.
     */
    protected function calculatePeriodEnd($from, BillingCycle $cycle)
    {
        return match ($cycle) {
            BillingCycle::Monthly => $from->copy()->addMonth(),
            BillingCycle::Quarterly => $from->copy()->addMonths(3),
            BillingCycle::Yearly => $from->copy()->addYear(),
        };
    }
}
