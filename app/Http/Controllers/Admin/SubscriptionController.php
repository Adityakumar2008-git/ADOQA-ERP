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
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    /**
     * Provision a subscription for a business tenant.
     */
    public function store(ProvisionSubscriptionRequest $request, ProvisionSubscription $action): JsonResponse|RedirectResponse
    {
        $business = Business::findOrFail($request->validated('business_id'));
        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));

        try {
            $subscription = $action->execute(
                actor: $request->user(),
                business: $business,
                plan: $plan,
                options: [
                    'skip_trial' => $request->boolean('skip_trial'),
                ]
            );
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Subscription provisioned successfully.',
                'subscription' => $subscription,
            ], 201);
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', "Subscription for plan '{$plan->name}' provisioned successfully.");
    }

    /**
     * Transition a subscription to a new lifecycle state.
     */
    public function transition(TransitionSubscriptionRequest $request, Subscription $subscription, TransitionSubscriptionStatus $action): JsonResponse|RedirectResponse
    {
        $targetStatus = SubscriptionStatus::from($request->validated('status'));

        try {
            $updatedSubscription = $action->execute(
                actor: $request->user(),
                subscription: $subscription,
                targetStatus: $targetStatus,
                reason: $request->validated('reason')
            );
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Subscription state transition successful.',
                'subscription' => $updatedSubscription,
            ]);
        }

        return redirect()->route('admin.businesses.show', $subscription->business_id)
            ->with('success', "Subscription transitioned to {$updatedSubscription->status->value} successfully.");
    }
}
