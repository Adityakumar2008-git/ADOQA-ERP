<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Subscription\ProvisionSubscription;
use App\Actions\Subscription\TransitionSubscriptionStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProvisionSubscriptionRequest;
use App\Http\Requests\Admin\TransitionSubscriptionRequest;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /**
     * Provision a subscription for a business tenant.
     */
    public function store(ProvisionSubscriptionRequest $request, ProvisionSubscription $action): JsonResponse
    {
        $business = Business::findOrFail($request->validated('business_id'));
        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));

        $subscription = $action->execute(
            actor: $request->user(),
            business: $business,
            plan: $plan,
            options: [
                'skip_trial' => $request->boolean('skip_trial'),
            ]
        );

        return response()->json([
            'message' => 'Subscription provisioned successfully.',
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * Transition a subscription to a new lifecycle state.
     */
    public function transition(TransitionSubscriptionRequest $request, Subscription $subscription, TransitionSubscriptionStatus $action): JsonResponse
    {
        $targetStatus = SubscriptionStatus::from($request->validated('status'));

        $updatedSubscription = $action->execute(
            actor: $request->user(),
            subscription: $subscription,
            targetStatus: $targetStatus,
            reason: $request->validated('reason')
        );

        return response()->json([
            'message' => 'Subscription state transition successful.',
            'subscription' => $updatedSubscription,
        ]);
    }
}
