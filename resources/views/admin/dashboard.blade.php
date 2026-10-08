@extends('layouts.admin')

@section('title', 'System Overview')
@section('page_title', 'System Overview')

@section('content')
<div class="space-y-8">
    <!-- Header with Quick Action Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Platform System Overview</h1>
            <p class="text-sm text-slate-500 mt-1">Real-time status of multi-tenant businesses, subscriptions, and platform infrastructure.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.businesses.create') }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-xs text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Provision Business
            </a>
            <a href="{{ route('admin.subscription-plans.create') }}"
               class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-lg shadow-xs text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Plan
            </a>
        </div>
    </div>

    <!-- Active Real Domain Metrics Grid -->
    <div>
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">Live Platform Infrastructure</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Active Tenants Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Tenants</p>
                    <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($metrics['active_tenants']) }}</span>
                    <span class="ml-2 text-xs font-medium text-slate-500">of {{ number_format($metrics['total_tenants']) }} total</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-emerald-700 font-medium">Database Verified</span>
                    <a href="{{ route('admin.businesses.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">View all &rarr;</a>
                </div>
            </div>

            <!-- Platform Users Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tenant Users</p>
                    <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($metrics['platform_users']) }}</span>
                    <span class="ml-2 text-xs font-medium text-slate-500">business members</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
                    Strict tenant role isolation enforced
                </div>
            </div>

            <!-- Active Subscriptions Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Subscriptions</p>
                    <span class="p-2 rounded-lg bg-sky-50 text-sky-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($metrics['active_subscriptions']) }}</span>
                    <span class="ml-2 text-xs font-medium text-slate-500">tenants on active plan</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">State machine active</span>
                    <a href="{{ route('admin.subscription-plans.index') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">Plans &rarr;</a>
                </div>
            </div>

            <!-- Configured Plans Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-2xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Configured Plans</p>
                    <span class="p-2 rounded-lg bg-purple-50 text-purple-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3 flex items-baseline">
                    <span class="text-2xl font-bold text-slate-900">{{ number_format($metrics['active_plans']) }}</span>
                    <span class="ml-2 text-xs font-medium text-slate-500">published plans</span>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
                    Offline billing model reference
                </div>
            </div>
        </div>
    </div>

    <!-- Truthful Zero States for Future Financial & Partner Modules -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">Commercial & Financial Architecture</h2>
            <span class="text-xs text-slate-500 font-medium">Billing Ledger & Partner Program scheduled for Phase 2+</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total Revenue -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Total Revenue</p>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-200/80 px-1.5 py-0.5 rounded">Manual/Offline</span>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['total_revenue'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">No online gateway; offline collected</p>
            </div>

            <!-- Net Platform Profit -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Net Platform Profit</p>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-200/80 px-1.5 py-0.5 rounded">Phase 2</span>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['net_profit'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Requires financial ledger activation</p>
            </div>

            <!-- Sales Partners -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Sales Partners</p>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-200/80 px-1.5 py-0.5 rounded">Phase 2+</span>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['sales_partners'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Partner network onboarding pending</p>
            </div>

            <!-- Pending Payouts -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Pending Payouts</p>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-200/80 px-1.5 py-0.5 rounded">Phase 2+</span>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['pending_payouts'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Agent commissions pending module</p>
            </div>

            <!-- Partner Commissions -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Partner Commissions</p>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-200/80 px-1.5 py-0.5 rounded">Phase 2+</span>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['partner_commissions'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Calculated upon partner activation</p>
            </div>
        </div>
    </div>

    <!-- Architectural Information & Empty State Note -->
    <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-xl p-5 text-indigo-950">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-indigo-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-xs space-y-1">
                <p class="font-bold text-sm text-indigo-900">Subscription Billing Model Specification (Phase 1D-C Baseline)</p>
                <p class="text-indigo-800">
                    ADOQA does not charge subscriptions online. No Razorpay/Stripe webhooks or card checkouts exist. Subscription assignments and billing cycles are managed manually and authoritatively by platform administrators. Financial trend tracking and ledger summaries will be connected in Phase 2.
                </p>
            </div>
        </div>
    </div>

    <!-- Recent Businesses Provisioned -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Recently Provisioned Businesses</h3>
                <p class="text-xs text-slate-500 mt-0.5">Most recent tenants created through Master Admin surface.</p>
            </div>
            <a href="{{ route('admin.businesses.index') }}"
               class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                View all tenants &rarr;
            </a>
        </div>

        @if ($recentBusinesses->isEmpty())
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500">No businesses have been provisioned yet.</p>
                <div class="mt-3">
                    <a href="{{ route('admin.businesses.create') }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        + Provision your first business
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                        <tr>
                            <th scope="col" class="px-5 py-3">Business</th>
                            <th scope="col" class="px-5 py-3">Owner</th>
                            <th scope="col" class="px-5 py-3">Type</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3">Subscription</th>
                            <th scope="col" class="px-5 py-3">Created</th>
                            <th scope="col" class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($recentBusinesses as $business)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="font-semibold text-slate-900">{{ $business->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $business->slug }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <div class="text-slate-800 font-medium">{{ $business->owner?->name ?? 'None' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $business->owner?->email ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap capitalize text-slate-600">
                                    {{ $business->business_type }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($business->status->value === 'active')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    @elseif ($business->status->value === 'suspended')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            Suspended
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ ucfirst($business->status->value) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if ($business->activeSubscription)
                                        <div class="font-medium text-slate-800">{{ $business->activeSubscription->plan->name }}</div>
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-sky-50 text-sky-700">
                                            {{ ucfirst($business->activeSubscription->status->value) }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 italic">No Active Plan</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-slate-500">
                                    {{ $business->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.businesses.show', $business) }}"
                                       class="font-semibold text-indigo-600 hover:text-indigo-800">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
