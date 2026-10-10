@extends('layouts.admin')

@section('title', 'Businesses & Tenants')
@section('page_title', 'Businesses / Tenants')

@section('content')
<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Registered Businesses</h1>
            <p class="text-sm text-slate-500 mt-1">Tenant isolation directory. Master Admin maintains platform authority without joining tenant memberships.</p>
        </div>
        <div>
            <a href="{{ route('admin.businesses.create') }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-xs text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Provision Business
            </a>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
        <form action="{{ route('admin.businesses.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $currentSearch }}"
                       placeholder="Search by business name, slug, email, or phone..."
                       class="block w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="sm:w-48">
                <select name="status"
                        class="block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ $currentStatus === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit"
                        class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if ($currentSearch || $currentStatus)
                    <a href="{{ route('admin.businesses.index') }}"
                       class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Businesses Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if ($businesses->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="text-sm font-bold text-slate-900">No businesses found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    @if ($currentSearch || $currentStatus)
                        No businesses matched your search filters. Try clearing the filter criteria.
                    @else
                        No businesses have been provisioned on the platform yet.
                    @endif
                </p>
                <div class="mt-4">
                    @if ($currentSearch || $currentStatus)
                        <a href="{{ route('admin.businesses.index') }}"
                           class="inline-flex items-center px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50">
                            Clear filters
                        </a>
                    @else
                        <a href="{{ route('admin.businesses.create') }}"
                           class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                            + Provision First Business
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">Business</th>
                            <th scope="col" class="px-5 py-3.5">Owner</th>
                            <th scope="col" class="px-5 py-3.5">Type</th>
                            <th scope="col" class="px-5 py-3.5">Status</th>
                            <th scope="col" class="px-5 py-3.5">Current Subscription</th>
                            <th scope="col" class="px-5 py-3.5">Plan Expiry</th>
                            <th scope="col" class="px-5 py-3.5">Created Date</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($businesses as $business)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                                            @if ($business->logo_url)
                                                <img src="{{ $business->logo_url }}" alt="{{ $business->name }}" class="w-full h-full object-contain">
                                            @else
                                                <span class="text-xs font-bold text-slate-500">{{ strtoupper(substr($business->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $business->name }}</div>
                                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $business->slug }}</div>
                                            @if ($business->legal_name && $business->legal_name !== $business->name)
                                                <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $business->legal_name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ $business->owner?->name ?? 'No Owner' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $business->owner?->email ?? '-' }}</div>
                                    @if ($business->owner?->phone)
                                        <div class="text-[11px] text-slate-400">{{ $business->owner->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap capitalize text-slate-600 font-medium">
                                    {{ $business->business_type }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
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
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($business->activeSubscription)
                                        <div class="font-semibold text-slate-900">{{ $business->activeSubscription->plan->name }}</div>
                                        <div class="mt-0.5">
                                            @php
                                                $subStatus = $business->activeSubscription->status->value;
                                            @endphp
                                            @if ($subStatus === 'active')
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">Active</span>
                                            @elseif ($subStatus === 'trial')
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-sky-50 text-sky-700">Trial</span>
                                            @elseif ($subStatus === 'past_due')
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-amber-50 text-amber-700">Past Due</span>
                                            @elseif ($subStatus === 'suspended')
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-50 text-rose-700">Suspended</span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ ucfirst($subStatus) }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">No Subscription</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                                    @if ($business->activeSubscription?->ends_at)
                                        {{ $business->activeSubscription->ends_at->format('M d, Y') }}
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-500">
                                    {{ $business->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.businesses.show', $business) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 border border-slate-200 rounded-md text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($businesses->hasPages())
                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50">
                    {{ $businesses->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
