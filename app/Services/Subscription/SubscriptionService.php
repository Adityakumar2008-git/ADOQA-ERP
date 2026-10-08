<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Subscription;

class SubscriptionService
{
    /**
     * Retrieve the active/usable subscription for a business.
     */
    public function getActiveSubscription(Business|string $business): ?Subscription
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        return Subscription::where('business_id', $businessId)
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ])
            ->with('plan')
            ->latest('starts_at')
            ->first();
    }

    /**
     * Check if a business has a usable active subscription.
     */
    public function hasActiveSubscription(Business|string $business): bool
    {
        return $this->getActiveSubscription($business) !== null;
    }
}
