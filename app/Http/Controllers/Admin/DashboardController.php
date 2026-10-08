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
            // Truthful zero states for future domain modules
            'total_revenue' => '0.00',
            'net_profit' => '0.00',
            'sales_partners' => 0,
            'pending_payouts' => 0,
            'partner_commissions' => '0.00',
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
