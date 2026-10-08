<?php

namespace App\Actions\Subscription;

use App\Enums\BillingCycle;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateSubscriptionPlan
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Update an existing subscription plan.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     */
    public function execute(User $actor, SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        if ($this->authorizationService->cannot($actor, 'subscriptions.manage')) {
            throw new AuthorizationException('Unauthorized: Only platform administrators can manage subscription plans.');
        }

        return DB::transaction(function () use ($actor, $plan, $data) {
            $oldValues = [
                'name' => $plan->name,
                'price' => (string) $plan->price,
                'is_active' => $plan->is_active,
            ];

            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = trim($data['name']);
            }
            if (isset($data['description'])) {
                $updateData['description'] = trim($data['description']);
            }
            if (isset($data['price'])) {
                $updateData['price'] = $data['price'];
            }
            if (isset($data['billing_cycle'])) {
                $updateData['billing_cycle'] = $data['billing_cycle'] instanceof BillingCycle
                    ? $data['billing_cycle']
                    : BillingCycle::from($data['billing_cycle']);
            }
            if (isset($data['trial_days'])) {
                $updateData['trial_days'] = $data['trial_days'];
            }
            if (isset($data['features'])) {
                $updateData['features'] = $data['features'];
            }
            if (array_key_exists('is_active', $data)) {
                $updateData['is_active'] = $data['is_active'];
            }

            $plan->update($updateData);

            AuditLog::create([
                'business_id' => null,
                'user_id' => $actor->id,
                'action' => 'subscription_plan.updated',
                'auditable_type' => SubscriptionPlan::class,
                'auditable_id' => $plan->id,
                'payload' => [
                    'old' => $oldValues,
                    'new' => [
                        'name' => $plan->name,
                        'price' => (string) $plan->price,
                        'is_active' => $plan->is_active,
                    ],
                ],
            ]);

            return $plan;
        });
    }
}
