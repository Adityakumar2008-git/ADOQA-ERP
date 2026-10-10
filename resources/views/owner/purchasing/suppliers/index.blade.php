@extends('layouts.owner')

@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Suppliers Directory')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Supplier Directory"
        subtitle="Manage business vendors, procurement contacts, credit limits, and subledger payables."
    >
        @if($authService->can(auth()->user(), 'suppliers.manage', $tenant))
            <x-slot:actions>
                <x-button variant="primary" :href="route('owner.purchasing.suppliers.create')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Supplier
                </x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <!-- Search Toolbar -->
    <x-card>
        <form method="GET" action="{{ route('owner.purchasing.suppliers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-3">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Search Suppliers</label>
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Search by vendor name, code, phone..."
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] pl-8 py-2"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div class="flex items-end space-x-2">
                <x-button type="submit" variant="secondary" size="sm" class="w-full justify-center">Filter</x-button>
                @if(request('search'))
                    <a href="{{ route('owner.purchasing.suppliers.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1.5 flex items-center">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($suppliers->isEmpty())
            <x-empty-state
                title="No suppliers found"
                description="{{ request('search') ? 'No suppliers match your search query.' : 'Create your first supplier to begin raising purchase orders.' }}"
                actionText="{{ request('search') || ! $authService->can(auth()->user(), 'suppliers.manage', $tenant) ? '' : 'Add Supplier' }}"
                :actionUrl="request('search') || ! $authService->can(auth()->user(), 'suppliers.manage', $tenant) ? null : route('owner.purchasing.suppliers.create')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Vendor & Code</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4 text-right">Credit Limit</th>
                            <th class="py-3 px-4 text-right">Current Outstanding</th>
                            <th class="py-3 px-4 text-center">Orders & Bills</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($suppliers as $supplier)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    <a href="{{ route('owner.purchasing.suppliers.show', $supplier) }}" class="font-bold text-slate-900 hover:text-[#1C9640] block">
                                        {{ $supplier->name }}
                                    </a>
                                    <span class="text-[11px] text-slate-500 font-mono">{{ $supplier->code }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    @if($supplier->phone)
                                        <div class="font-medium text-slate-800">{{ $supplier->phone }}</div>
                                    @endif
                                    @if($supplier->email)
                                        <div class="text-[11px] text-slate-500">{{ $supplier->email }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    {{ $supplier->credit_limit !== null ? $tenant->currency . ' ' . number_format((float) $supplier->credit_limit, 2) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-sm {{ (float) $supplier->current_balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $tenant->currency }} {{ number_format((float) $supplier->current_balance, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center text-slate-600 font-mono text-[11px]">
                                    {{ $supplier->purchase_orders_count }} POs / {{ $supplier->purchase_bills_count }} Bills
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if($supplier->status->value === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                                        {{ ucfirst($supplier->status->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <a href="{{ route('owner.purchasing.suppliers.show', $supplier) }}" class="font-semibold text-xs text-[#1C9640] hover:text-[#13682C]">
                                        View
                                    </a>
                                    <a href="{{ route('owner.purchasing.suppliers.ledger', $supplier) }}" class="font-semibold text-xs text-slate-600 hover:text-slate-800">
                                        Subledger
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $suppliers->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
