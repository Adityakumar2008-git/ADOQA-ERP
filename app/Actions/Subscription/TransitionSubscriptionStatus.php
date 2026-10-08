<?php

namespace App\Actions\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TransitionSubscriptionStatus
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Transition a subscription to a new state following the approved state machine.
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Subscription $subscription, SubscriptionStatus $targetStatus, ?string $reason = null): Subscription
    {
        // 1. Authorize: Platform Admin with subscriptions.manage, OR business owner cancelling
        $isPlatformAdmin = $this->authorizationService->can($actor, 'subscriptions.manage');
        $isBusinessOwner = $actor->membership?->business_id === $subscription->business_id
            && $actor->membership?->is_owner;

        if ($isPlatformAdmin) {
            // Platform admin can execute any valid lifecycle transition
        } elseif ($isBusinessOwner && $targetStatus === SubscriptionStatus::Cancelled) {
            // Business owner is allowed to cancel their active or trial subscription
        } else {
            throw new AuthorizationException('Unauthorized: You do not have permission to perform this subscription transition.');
        }

        // 2. Validate state machine transition rules from 07_WORKFLOWS_AND_STATE_MACHINES.md
        if (! $subscription->status->canTransitionTo($targetStatus)) {
            throw new DomainException("Invalid subscription transition from [{$subscription->status->value}] to [{$targetStatus->value}].");
        }

        return DB::transaction(function () use ($actor, $subscription, $targetStatus, $reason) {
            $oldStatus = $subscription->status;

            $updateData = [
                'status' => $targetStatus,
            ];

            if ($targetStatus === SubscriptionStatus::Cancelled) {
                $updateData['cancelled_at'] = now();
            }

            $subscription->update($updateData);

            AuditLog::create([
                'business_id' => $subscription->business_id,
                'user_id' => $actor->id,
                'action' => 'subscription.status_transitioned',
                'auditable_type' => Subscription::class,
                'auditable_id' => $subscription->id,
                'payload' => [
                    'from' => $oldStatus->value,
                    'to' => $targetStatus->value,
                    'reason' => $reason,
                ],
            ]);

            return $subscription;
        });
    }
}
