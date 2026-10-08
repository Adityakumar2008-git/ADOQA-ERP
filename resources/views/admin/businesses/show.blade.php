@extends('layouts.admin')

@section('title', $business->name . ' — Tenant Details')
@section('page_title', 'Business Details')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.businesses.index') }}" class="hover:text-slate-800">Businesses</a>
                <span>/</span>
                <span class="text-slate-800 font-medium">{{ $business->name }}</span>
            </nav>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $business->name }}</h1>
                @if ($business->status->value === 'active')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active Tenant
                    </span>
                @elseif ($business->status->value === 'suspended')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        Suspended
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ ucfirst($business->status->value) }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 font-mono mt-1">Tenant ID: {{ $business->id }} &bull; Slug: {{ $business->slug }}</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.businesses.index') }}"
               class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                &larr; Back to List
            </a>
        </div>
    </div>

    <!-- Main Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Business & Owner Profile -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Business Profile Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Commercial Profile</h2>
                    <span class="text-xs font-medium text-slate-400 capitalize">Type: {{ $business->business_type }}</span>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Legal Name</dt>
                            <dd class="mt-1 text-slate-900 font-medium">{{ $business->legal_name ?? $business->name }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Tax / GST Number</dt>
                            <dd class="mt-1 text-slate-900 font-mono font-medium">{{ $business->tax_number ?? 'Not Configured' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Contact Email</dt>
                            <dd class="mt-1 text-slate-900">{{ $business->email ?? 'None' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Contact Phone</dt>
                            <dd class="mt-1 text-slate-900">{{ $business->phone ?? 'None' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Operating Currency</dt>
                            <dd class="mt-1 text-slate-900 font-bold">{{ $business->currency }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Operating Timezone</dt>
                            <dd class="mt-1 text-slate-900 font-mono">{{ $business->timezone }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Registered Address</dt>
                            <dd class="mt-1 text-slate-800">
                                @if ($business->address_line_1 || $business->city)
                                    {{ $business->address_line_1 }}
                                    @if ($business->address_line_2), {{ $business->address_line_2 }} @endif
                                    <br>{{ $business->city }}, {{ $business->state }} {{ $business->postal_code }} ({{ $business->country }})
                                @else
                                    <span class="text-slate-400 italic">No physical address specified.</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Provisioned On</dt>
                            <dd class="mt-1 text-slate-700">{{ $business->created_at->format('M d, Y H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Owner Identity Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Primary Account Owner</h2>
                        <p class="text-xs text-slate-500">Sole owner account with isolated business scope.</p>
                    </div>
                    @if ($business->owner)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ ucfirst($business->owner->status->value) }}
                        </span>
                    @endif
                </div>
                <div class="p-6">
                    @if ($business->owner)
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-xs">
                            <div>
                                <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Owner Name</dt>
                                <dd class="mt-1 text-slate-900 font-bold text-sm">{{ $business->owner->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Owner Email</dt>
                                <dd class="mt-1 text-slate-900">{{ $business->owner->email }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Owner Phone</dt>
                                <dd class="mt-1 text-slate-900">{{ $business->owner->phone ?? 'Not provided' }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Platform Admin Scope</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">
                                        False (Strict Tenant Scope)
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    @else
                        <div class="text-xs text-slate-500 italic">No primary owner account linked.</div>
                    @endif
                </div>
            </div>

            <!-- Audit Activity History -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Audit & Lifecycle Activity</h2>
                        <p class="text-xs text-slate-500">Immutable ledger events recorded for this business tenant.</p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">
                        {{ $auditLogs->count() }} Events
                    </span>
                </div>
                @if ($auditLogs->isEmpty())
                    <div class="p-6 text-center text-xs text-slate-400 italic">
                        No audit events recorded for this business yet.
                    </div>
                @else
                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold sticky top-0">
                                <tr>
                                    <th scope="col" class="px-6 py-2.5">Action</th>
                                    <th scope="col" class="px-6 py-2.5">Actor</th>
                                    <th scope="col" class="px-6 py-2.5">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($auditLogs as $log)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <span class="font-mono font-semibold text-slate-800">{{ $log->action }}</span>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-slate-600">
                                            {{ $log->user?->name ?? 'System' }}
                                            <span class="text-[10px] text-slate-400">({{ $log->user?->email ?? '-' }})</span>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-slate-500 text-[11px]">
                                            {{ $log->created_at->format('M d, Y H:i:s') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Col: Subscription Operations -->
        <div class="space-y-6">
            <!-- Active Subscription Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Subscription Status</h2>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                        Offline Model
                    </span>
                </div>

                <div class="p-6 space-y-5">
                    @php
                        $sub = $business->activeSubscription;
                    @endphp

                    @if ($sub)
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-sm">{{ $sub->plan->name }}</span>
                                @php $statusVal = $sub->status->value; @endphp
                                @if ($statusVal === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                                @elseif ($statusVal === 'trial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-sky-100 text-sky-800">Trial Period</span>
                                @elseif ($statusVal === 'past_due')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">Past Due</span>
                                @elseif ($statusVal === 'suspended')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">Suspended</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-200 text-slate-800">{{ ucfirst($statusVal) }}</span>
                                @endif
                            </div>

                            <div class="space-y-1.5 pt-1 text-[11px] text-slate-600">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Configured Price:</span>
                                    <span class="font-semibold text-slate-800">₹{{ number_format((float) $sub->plan->price, 2) }} / {{ $sub->plan->billing_cycle->label() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Started:</span>
                                    <span class="font-medium text-slate-800">{{ $sub->starts_at->format('M d, Y') }}</span>
                                </div>
                                @if ($sub->trial_ends_at)
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Trial Ends:</span>
                                        <span class="font-medium text-sky-700">{{ $sub->trial_ends_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Period Ends / Expiry:</span>
                                    <span class="font-medium text-slate-800">{{ $sub->ends_at ? $sub->ends_at->format('M d, Y') : 'Indefinite' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Lifecycle State Transitions -->
                        @php
                            $allowedTransitions = $sub->status->allowedTransitions();
                        @endphp

                        @if (!empty($allowedTransitions))
                            <div class="border-t border-slate-100 pt-4 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Administrative State Transitions</h3>
                                <p class="text-[11px] text-slate-500">Strictly follows the canonical subscription state machine.</p>

                                <div class="space-y-2">
                                    @foreach ($allowedTransitions as $targetStatus)
                                        <form action="{{ route('admin.subscriptions.transition', $sub) }}" method="POST"
                                              onsubmit="return confirm('Confirm state transition to [{{ $targetStatus->value }}]?');">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $targetStatus->value }}">
                                            <div class="flex items-center space-x-2">
                                                <button type="submit"
                                                        class="w-full text-left px-3 py-2 rounded-lg text-xs font-semibold border transition-colors flex items-center justify-between
                                                        @if ($targetStatus->value === 'active') bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100
                                                        @elseif ($targetStatus->value === 'suspended') bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100
                                                        @elseif ($targetStatus->value === 'cancelled' || $targetStatus->value === 'expired') bg-rose-50 text-rose-800 border-rose-300 hover:bg-rose-100
                                                        @else bg-slate-50 text-slate-800 border-slate-300 hover:bg-slate-100 @endif">
                                                    <span>Transition to <strong>{{ ucfirst($targetStatus->value) }}</strong></span>
                                                    <span class="text-[10px] uppercase font-bold">&rarr;</span>
                                                </button>
                                            </div>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="p-3 bg-slate-100 rounded-lg text-[11px] text-slate-600">
                                This subscription is in a terminal state (<strong>{{ ucfirst($sub->status->value) }}</strong>) and has no further transitions.
                            </div>
                        @endif

                    @else
                        <!-- No Active Subscription State -->
                        <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-2">
                            <p class="font-bold">No Active Subscription</p>
                            <p class="text-[11px] text-amber-800">This business tenant has no active, trial, or past-due subscription provisioned.</p>
                        </div>

                        <!-- Assign Subscription Plan Form -->
                        <form action="{{ route('admin.subscriptions.store') }}" method="POST" class="space-y-4 pt-2">
                            @csrf
                            <input type="hidden" name="business_id" value="{{ $business->id }}">

                            <div>
                                <label for="assign_plan_id" class="block text-xs font-semibold text-slate-700">Assign Subscription Plan</label>
                                <select name="plan_id" id="assign_plan_id" required
                                        class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select a Plan...</option>
                                    @foreach ($availablePlans as $plan)
                                        <option value="{{ $plan->id }}">
                                            {{ $plan->name }} (₹{{ number_format((float) $plan->price, 2) }} / {{ $plan->billing_cycle->label() }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="skip_trial" id="assign_skip_trial" value="1"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
                                <label for="assign_skip_trial" class="ml-2 block text-xs text-slate-700 font-medium">
                                    Skip trial period
                                </label>
                            </div>

                            <button type="submit"
                                    class="w-full py-2 px-4 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs">
                                Provision Subscription
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
