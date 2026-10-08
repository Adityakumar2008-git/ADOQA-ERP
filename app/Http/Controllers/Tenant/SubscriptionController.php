<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Subscription\TransitionSubscriptionStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display the current active subscription for this tenant.
     * Strictly server-authoritative tenant context.
     */
    public function show(Request $request, SubscriptionService $subscriptionService): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $activeSubscription = $subscriptionService->getActiveSubscription($tenant);

        if (! $activeSubscription) {
            return response()->json([
                'has_active_subscription' => false,
                'subscription' => null,
            ]);
        }

        return response()->json([
            'has_active_subscription' => true,
            'subscription' => $activeSubscription,
        ]);
    }

    /**
     * Cancel the current subscription (Owner capability).
     */
    public function cancel(Request $request, SubscriptionService $subscriptionService, TransitionSubscriptionStatus $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $subscription = $subscriptionService->getActiveSubscription($tenant);

        if (! $subscription) {
            abort(404, 'No active subscription found to cancel.');
        }

        $cancelledSubscription = $action->execute(
            actor: $request->user(),
            subscription: $subscription,
            targetStatus: SubscriptionStatus::Cancelled,
            reason: $request->input('reason', 'Cancelled by business owner')
        );

        return response()->json([
            'message' => 'Subscription cancelled successfully.',
            'subscription' => $cancelledSubscription,
        ]);
    }
}
