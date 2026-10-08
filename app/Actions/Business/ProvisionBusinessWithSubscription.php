<?php

namespace App\Actions\Business;

use App\Actions\Subscription\ProvisionSubscription;
use App\Models\Business;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ProvisionBusinessWithSubscription
{
    public function __construct(
        protected CreateBusiness $createBusiness,
        protected ProvisionSubscription $provisionSubscription,
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Atomically coordinate business tenant creation and optional initial subscription provisioning.
     * Reuses canonical CreateBusiness and ProvisionSubscription actions without duplicating domain logic.
     *
     * @param  array<string, mixed>  $businessData
     * @param  array<string, mixed>  $subscriptionOptions
     *
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function execute(
        User $actor,
        array $businessData,
        ?SubscriptionPlan $plan = null,
        array $subscriptionOptions = []
    ): Business {
        // 1. Upfront authorization checks
        if ($this->authorizationService->cannot($actor, 'businesses.create')) {
            throw new AuthorizationException('Only platform administrators with [businesses.create] permission can provision a business.');
        }

        if ($plan && $this->authorizationService->cannot($actor, 'subscriptions.manage')) {
            throw new AuthorizationException('Only platform administrators with [subscriptions.manage] permission can provision an initial subscription.');
        }

        // 2. Atomic boundary: execute both authoritative actions within a single transaction
        return DB::transaction(function () use ($actor, $businessData, $plan, $subscriptionOptions) {
            $business = $this->createBusiness->execute(
                actor: $actor,
                data: $businessData
            );

            if ($plan) {
                $this->provisionSubscription->execute(
                    actor: $actor,
                    business: $business,
                    plan: $plan,
                    options: $subscriptionOptions
                );
            }

            return $business->fresh(['ownerMembership.user', 'activeSubscription.plan', 'members.user', 'userRoles.role']);
        });
    }
}
