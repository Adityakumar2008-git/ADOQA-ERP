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
            <!-- Tenant Module Controls Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tenant Module Controls</h2>
                        <p class="text-xs text-slate-500">Enable or disable functional ERP modules for this tenant. Disabled modules deny access on the server.</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                        {{ count(array_filter($modules ?? [], fn($m) => $m['is_enabled'])) }} / {{ count($modules ?? []) }} Enabled
                    </span>
                </div>
                <form action="{{ route('admin.businesses.modules.update', $business) }}" method="POST" class="p-6">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($modules ?? [] as $module)
                            <div class="p-3.5 rounded-lg border {{ $module['is_enabled'] ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/50' }} flex items-start justify-between space-x-3">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-slate-900">{{ $module['name'] }}</span>
                                        <span class="font-mono text-[10px] px-1.5 py-0.5 rounded {{ $module['is_enabled'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                            {{ $module['slug'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 leading-relaxed">{{ $module['description'] }}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                                    <input type="hidden" name="modules[{{ $module['slug'] }}]" value="0">
                                    <input type="checkbox" name="modules[{{ $module['slug'] }}]" value="1" {{ $module['is_enabled'] ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-xs transition-colors">
                            Save Module Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tenant Role & Permission Overrides Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tenant Role & Permission Overrides</h2>
                        <p class="text-xs text-slate-500">Fine-tune canonical role templates for this business without modifying global defaults.</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                        {{ count($roleOverrides ?? []) }} Active Overrides
                    </span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- New Override Form -->
                    <form action="{{ route('admin.businesses.overrides.store', $business) }}" method="POST" class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        @csrf
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Configure Role Override</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Business Role</label>
                                <select name="role_id" required class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Role...</option>
                                    @foreach ($businessRoles ?? [] as $bRole)
                                        <option value="{{ $bRole->id }}">{{ $bRole->name }} ({{ $bRole->slug }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Business Permission</label>
                                <select name="permission_id" required class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select Permission...</option>
                                    @foreach ($businessPermissions ?? [] as $bPerm)
                                        <option value="{{ $bPerm->id }}">{{ $bPerm->name }} [{{ $bPerm->slug }}] ({{ $bPerm->group }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Override Action</label>
                                <select name="state" required class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="allow">ALLOW (Explicit Grant)</option>
                                    <option value="deny">DENY (Explicit Suppress)</option>
                                    <option value="inherit">INHERIT (Reset to Template)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg transition-colors shadow-xs">
                                Apply Override
                            </button>
                        </div>
                    </form>

                    <!-- Existing Overrides List -->
                    @if (($roleOverrides ?? collect())->isEmpty())
                        <div class="text-center py-6 text-slate-400 text-xs italic bg-slate-50/50 rounded-lg border border-dashed border-slate-200">
                            No role overrides configured for this tenant. All roles inherit canonical default permissions.
                        </div>
                    @else
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-2.5">Role</th>
                                        <th class="px-4 py-2.5">Permission</th>
                                        <th class="px-4 py-2.5">Override State</th>
                                        <th class="px-4 py-2.5">Updated</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($roleOverrides as $ov)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2.5 font-semibold text-slate-800">{{ $ov->role?->name }}</td>
                                            <td class="px-4 py-2.5 font-mono text-slate-700 text-[11px]">{{ $ov->permission?->slug }}</td>
                                            <td class="px-4 py-2.5">
                                                @if ($ov->isAllow())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                                        ALLOW
                                                    </span>
                                                @elseif ($ov->isDeny())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">
                                                        DENY
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700">
                                                        INHERIT
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-slate-500 text-[11px]">{{ $ov->updated_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
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
