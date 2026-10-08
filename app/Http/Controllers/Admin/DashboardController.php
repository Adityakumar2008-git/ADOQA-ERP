<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Master Admin System Overview dashboard.
     */
    public function index(Request $request): View|JsonResponse
    {
        $activeTenantsCount = Business::where('status', BusinessStatus::Active)->count();
        $totalTenantsCount = Business::count();
        $platformUsersCount = User::where('is_platform_admin', false)->count();
        $activeSubscriptionsCount = Subscription::where('status', SubscriptionStatus::Active)->count();
        $activePlansCount = SubscriptionPlan::where('is_active', true)->count();

        $recentBusinesses = Business::query()
            ->with(['ownerMembership.user', 'activeSubscription.plan'])
            ->latest()
            ->take(5)
            ->get();

        $metrics = [
            'active_tenants' => $activeTenantsCount,
            'total_tenants' => $totalTenantsCount,
            'platform_users' => $platformUsersCount,
            'active_subscriptions' => $activeSubscriptionsCount,
            'active_plans' => $activePlansCount,
            // Truthful states for future domain modules (no underlying domain exists in Phase 1)
            'total_revenue' => 'Not available yet',
            'net_profit' => 'Not available yet',
            'sales_partners' => 'Not available yet',
            'pending_payouts' => 'Not available yet',
            'partner_commissions' => 'Not available yet',
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'metrics' => $metrics,
                'recent_businesses' => $recentBusinesses,
            ]);
        }

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'recentBusinesses' => $recentBusinesses,
        ]);
    }
}
