<?php

namespace App\Actions\Subscription;

use App\Enums\BillingCycle;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSubscriptionPlan
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Create a new subscription plan in platform scope.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $actor, array $data): SubscriptionPlan
    {
        if ($this->authorizationService->cannot($actor, 'subscriptions.manage')) {
            throw new AuthorizationException('Unauthorized: Only platform administrators can manage subscription plans.');
        }

        $slug = Str::slug($data['slug'] ?? $data['name']);

        if (SubscriptionPlan::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'A subscription plan with this code or slug already exists.',
            ]);
        }

        return DB::transaction(function () use ($actor, $data, $slug) {
            $plan = SubscriptionPlan::create([
                'name' => trim($data['name']),
                'slug' => $slug,
                'description' => isset($data['description']) ? trim($data['description']) : null,
                'price' => $data['price'] ?? 0.00,
                'currency' => strtoupper($data['currency'] ?? 'INR'),
                'billing_cycle' => $data['billing_cycle'] instanceof BillingCycle
                    ? $data['billing_cycle']
                    : BillingCycle::from($data['billing_cycle'] ?? 'monthly'),
                'trial_days' => $data['trial_days'] ?? 0,
                'features' => $data['features'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            AuditLog::create([
                'business_id' => null,
                'user_id' => $actor->id,
                'action' => 'subscription_plan.created',
                'auditable_type' => SubscriptionPlan::class,
                'auditable_id' => $plan->id,
                'payload' => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => (string) $plan->price,
                    'billing_cycle' => $plan->billing_cycle->value,
                ],
            ]);

            return $plan;
        });
    }
}
