@extends('layouts.owner')
@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Warehouses & Locations')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Warehouses & Stock Locations"
        subtitle="Manage business storage facilities, physical depots, and internal picking locations."
    >
        <x-slot:actions>
            @if($authService->can(auth()->user(), 'warehouses.manage', $tenant))
                <x-button variant="primary" :href="route('owner.inventory.warehouses.create')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Warehouse
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($warehouses as $warehouse)
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="font-bold text-slate-900 text-base">{{ $warehouse->name }}</h3>
                                @if($warehouse->is_default)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Default
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $warehouse->code }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                            @if($warehouse->status->value === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                            @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                            {{ ucfirst($warehouse->status->value) }}
                        </span>
                    </div>

                    @if($warehouse->address)
                        <p class="text-xs text-slate-600 mt-3 line-clamp-2">{{ $warehouse->address }}</p>
                    @endif

                    <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-slate-50 p-2.5 rounded-lg">
                            <span class="text-slate-500 text-[11px] block">Stock Records</span>
                            <span class="font-bold text-slate-900 text-sm">{{ number_format($warehouse->balances_count) }}</span>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-lg">
                            <span class="text-slate-500 text-[11px] block">Internal Locations</span>
                            <span class="font-bold text-slate-900 text-sm">{{ $warehouse->locations->count() }}</span>
                        </div>
                    </div>

                    @if($warehouse->locations->isNotEmpty())
                        <div class="mt-3">
                            <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Locations</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($warehouse->locations as $loc)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] bg-slate-100 text-slate-700">
                                        {{ $loc->name }} ({{ $loc->code }})
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Created {{ $warehouse->created_at->format('M d, Y') }}</span>
                    @if($authService->can(auth()->user(), 'warehouses.manage', $tenant))
                        <a href="{{ route('owner.inventory.warehouses.edit', $warehouse) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center">
                            Configure & Locations &rarr;
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <x-empty-state
                    title="No warehouses found"
                    description="Every business requires at least one default warehouse for receiving and stocking items."
                    :actionText="$authService->can(auth()->user(), 'warehouses.manage', $tenant) ? 'Create Default Warehouse' : null"
                    :actionUrl="$authService->can(auth()->user(), 'warehouses.manage', $tenant) ? route('owner.inventory.warehouses.create') : null"
                />
            </div>
        @endforelse
    </div>
</div>
@endsection
