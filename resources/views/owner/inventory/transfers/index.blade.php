@extends('layouts.owner')
@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Internal Stock Transfers')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Internal Stock Transfers"
        subtitle="Inter-warehouse stock movements, transit tracking, and discrepancy verification."
    >
        <x-slot:actions>
            @if($authService->can(auth()->user(), 'inventory.transfer', $tenant))
                <x-button variant="primary" :href="route('owner.inventory.transfers.create')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Transfer
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
        @if($transfers->isEmpty())
            <x-empty-state
                title="No stock transfers found"
                description="Move goods securely between storage locations with in-transit tracking and receipt validation."
                :actionText="$authService->can(auth()->user(), 'inventory.transfer', $tenant) ? 'Create Stock Transfer' : null"
                :actionUrl="$authService->can(auth()->user(), 'inventory.transfer', $tenant) ? route('owner.inventory.transfers.create') : null"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Transfer #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Source Depot</th>
                            <th class="py-3 px-4">Destination Depot</th>
                            <th class="py-3 px-4 text-center">Lines</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($transfers as $tr)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    {{ $tr->transfer_number }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ $tr->transfer_date ? $tr->transfer_date->format('Y-m-d') : $tr->created_at->format('Y-m-d') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $tr->sourceWarehouse->name }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $tr->destinationWarehouse->name }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700 font-mono">
                                    {{ $tr->lines_count }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if($tr->status->value === 'received') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($tr->status->value === 'dispatched') bg-amber-50 text-amber-700 border border-amber-200
                                        @elseif($tr->status->value === 'cancelled') bg-rose-50 text-rose-700 border border-rose-200
                                        @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                                        {{ ucfirst($tr->status->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    @if($authService->can(auth()->user(), 'inventory.transfer', $tenant))
                                        @if($tr->status->value === 'draft')
                                            <form method="POST" action="{{ route('owner.inventory.transfers.dispatch', $tr) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="font-semibold text-xs text-[#1C9640] hover:text-[#13682C]">Dispatch</button>
                                            </form>
                                            <form method="POST" action="{{ route('owner.inventory.transfers.cancel', $tr) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="font-semibold text-xs text-rose-600 hover:text-rose-800 ml-2">Cancel</button>
                                            </form>
                                        @elseif($tr->status->value === 'dispatched')
                                            <a href="{{ route('owner.inventory.transfers.receive', $tr) }}" class="font-semibold text-xs text-emerald-600 hover:text-emerald-800">
                                                Receive Depot &rarr;
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-xs">Completed</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 text-xs">{{ ucfirst($tr->status->value) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $transfers->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
