<?php

namespace App\Http\Controllers\Owner;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Services\Subscription\SubscriptionService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display the Business Owner ERP Dashboard.
     * Strictly tenant-scoped.
     */
    public function index(Request $request): View|JsonResponse
    {
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();

        $activeSubscription = $this->subscriptionService->getActiveSubscription($tenant);

        // Real database-backed operational metrics
        $memberCount = $tenant->members()
            ->where('status', MembershipStatus::Active)
            ->count();

        // Truthful states: non-existent future domains must NOT be represented as fake ₹0 or 0 metrics
        $metrics = [
            'active_members' => $memberCount,
            'business_status' => $tenant->status->label(),
            'subscription_plan' => $activeSubscription?->plan?->name ?? 'No Plan',
            'subscription_status' => $activeSubscription ? ucfirst($activeSubscription->status->value) : 'None',
            'subscription_ends_at' => $activeSubscription?->ends_at?->format('M d, Y'),
            'today_sales' => 'Not available yet',
            'today_profit' => 'Not available yet',
            'today_expenses' => 'Not available yet',
            'pending_receivables' => 'Not available yet',
            'customer_count' => 'Not available yet',
            'inventory_alerts' => 'Not available yet',
            'outstanding_payables' => 'Not available yet',
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'currency' => $tenant->currency,
                    'status' => $tenant->status->value,
                ],
                'subscription' => $activeSubscription ? [
                    'plan_name' => $activeSubscription->plan->name,
                    'status' => $activeSubscription->status->value,
                    'ends_at' => $activeSubscription->ends_at?->toIso8601String(),
                ] : null,
                'metrics' => $metrics,
            ]);
        }

        return view('owner.dashboard', [
            'tenant' => $tenant,
            'activeSubscription' => $activeSubscription,
            'metrics' => $metrics,
        ]);
    }
}
