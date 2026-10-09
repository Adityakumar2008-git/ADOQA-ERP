@extends('layouts.owner')
@inject('authService', 'App\Services\Auth\AuthorizationService')

@section('title', 'Purchase Orders')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Purchase Orders"
        subtitle="Procurement orders raised with suppliers, expected deliveries, and order fulfillment status."
    >
        <x-slot:actions>
            @if($authService->can(auth()->user(), 'purchases.create', $tenant))
                <x-button variant="primary" :href="route('owner.purchasing.orders.create')">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Purchase Order
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

    <!-- Orders Filter -->
    <x-card>
        <form method="GET" action="{{ route('owner.purchasing.orders.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label for="supplier_id" class="block text-xs font-semibold text-slate-600 mb-1">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" id="status" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                    <option value="">All Statuses</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="partially_received" @selected(request('status') === 'partially_received')>Partially Received</option>
                    <option value="received" @selected(request('status') === 'received')>Received</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="md:col-span-2 flex items-end space-x-2">
                <x-button type="submit" variant="secondary" size="sm" class="justify-center">Filter</x-button>
                @if(request()->hasAny(['supplier_id', 'status']))
                    <a href="{{ route('owner.purchasing.orders.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1.5 flex items-center">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($orders->isEmpty())
            <x-empty-state
                title="No purchase orders found"
                description="Raise purchase orders to authorize inventory procurement from suppliers."
                :actionText="$authService->can(auth()->user(), 'purchases.create', $tenant) ? 'Create Purchase Order' : null"
                :actionUrl="$authService->can(auth()->user(), 'purchases.create', $tenant) ? route('owner.purchasing.orders.create') : null"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">PO #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-4 text-center">Lines</th>
                            <th class="py-3 px-4 text-right">Total Amount</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <a href="{{ route('owner.purchasing.orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ $order->supplier->name }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700 font-mono">
                                    {{ $order->lines_count }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                                    {{ $tenant->currency }} {{ number_format((float) $order->total_amount, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if($order->status->value === 'approved') bg-indigo-50 text-indigo-700 border border-indigo-200
                                        @elseif($order->status->value === 'received') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($order->status->value === 'partially_received') bg-amber-50 text-amber-700 border border-amber-200
                                        @elseif($order->status->value === 'cancelled') bg-rose-50 text-rose-700 border border-rose-200
                                        @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                                        {{ str_replace('_', ' ', $order->status->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('owner.purchasing.orders.show', $order) }}" class="font-semibold text-xs text-indigo-600 hover:text-indigo-800">
                                        View Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
