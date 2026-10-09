@extends('layouts.owner')
@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Stock Adjustments')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Stock Adjustments"
        subtitle="Audited quantity corrections and physical stock-count reconciliations."
    >
        <x-slot:actions>
            @if($authService->can(auth()->user(), 'inventory.adjust', $tenant) || $authService->can(auth()->user(), 'inventory.manage', $tenant))
                <x-button variant="primary" :href="route('owner.inventory.adjustments.create')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Adjustment
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($adjustments->isEmpty())
            <x-empty-state
                title="No stock adjustments found"
                description="Use stock adjustments to reconcile variance between physical counts and system quantities."
                :actionText="($authService->can(auth()->user(), 'inventory.adjust', $tenant) || $authService->can(auth()->user(), 'inventory.manage', $tenant)) ? 'Create First Adjustment' : null"
                :actionUrl="($authService->can(auth()->user(), 'inventory.adjust', $tenant) || $authService->can(auth()->user(), 'inventory.manage', $tenant)) ? route('owner.inventory.adjustments.create') : null"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Adjustment #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Warehouse</th>
                            <th class="py-3 px-4">Reason</th>
                            <th class="py-3 px-4 text-center">Lines</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4">Posted By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($adjustments as $adj)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    {{ $adj->adjustment_number }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ $adj->adjustment_date ? $adj->adjustment_date->format('Y-m-d') : $adj->created_at->format('Y-m-d') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $adj->warehouse->name }}
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ $adj->reason }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700 font-mono">
                                    {{ $adj->lines_count }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $adj->status->value }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-[11px]">
                                    {{ $adj->poster?->name ?? $adj->creator?->name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($adjustments->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $adjustments->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
