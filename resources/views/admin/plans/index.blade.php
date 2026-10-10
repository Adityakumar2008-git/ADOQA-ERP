@extends('layouts.admin')

@section('title', 'Subscription Plans')
@section('page_title', 'Subscription Plans')

@section('content')
<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Subscription Plans</h1>
            <p class="text-sm text-slate-500 mt-1">Platform billing tiers configured for manual and offline onboarding.</p>
        </div>
        <div>
            <a href="{{ route('admin.subscription-plans.create') }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-xs text-xs font-semibold text-white bg-[#22B14C] hover:bg-[#1E9E44] transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Subscription Plan
            </a>
        </div>
    </div>

    <!-- Plans Grid / Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if ($plans->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <h3 class="text-sm font-bold text-slate-900">No subscription plans found</h3>
                <p class="text-xs text-slate-500 mt-1">Create subscription plans to provision business tenants.</p>
                <div class="mt-4">
                    <a href="{{ route('admin.subscription-plans.create') }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-white bg-[#22B14C] hover:bg-[#1E9E44]">
                        + Create First Plan
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">Plan Name</th>
                            <th scope="col" class="px-5 py-3.5">Price & Cycle</th>
                            <th scope="col" class="px-5 py-3.5">Trial Days</th>
                            <th scope="col" class="px-5 py-3.5">Status</th>
                            <th scope="col" class="px-5 py-3.5">Features</th>
                            <th scope="col" class="px-5 py-3.5">Subscribers</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($plans as $plan)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">{{ $plan->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $plan->slug }}</div>
                                    @if ($plan->description)
                                        <div class="text-[11px] text-slate-400 mt-0.5 max-w-xs">{{ $plan->description }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 text-sm">₹{{ number_format((float) $plan->price, 2) }}</div>
                                    <div class="text-[11px] text-slate-500">per {{ $plan->billing_cycle->label() }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($plan->trial_days > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                            {{ $plan->trial_days }} Days
                                        </span>
                                    @else
                                        <span class="text-slate-400">None</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($plan->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if (!empty($plan->features) && is_array($plan->features))
                                        <div class="flex flex-wrap gap-1 max-w-xs">
                                            @foreach (array_slice($plan->features, 0, 3) as $feat)
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-medium">
                                                    {{ $feat }}
                                                </span>
                                            @endforeach
                                            @if (count($plan->features) > 3)
                                                <span class="text-[10px] text-slate-400 self-center">+{{ count($plan->features) - 3 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">No features defined</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-bold text-slate-800">{{ $plan->subscriptions_count }}</span>
                                    <span class="text-slate-500 text-[11px]">tenants</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.subscription-plans.edit', $plan) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 border border-slate-200 rounded-md text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 hover:text-[#1C9640] transition-colors">
                                        Edit Plan
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
