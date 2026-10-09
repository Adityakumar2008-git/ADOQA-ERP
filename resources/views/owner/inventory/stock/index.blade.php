@extends('layouts.owner')

@section('title', 'Current Stock Balances')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Inventory Stock"
        subtitle="Real-time operational stock balances across all warehouses and tracking dimensions."
    >
        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <x-button variant="secondary" :href="route('owner.inventory.stock.ledger')">
                    Stock Ledger
                </x-button>
                <x-button variant="secondary" :href="route('owner.inventory.stock.opening_stock')">
                    Opening Stock
                </x-button>
                <x-button variant="primary" :href="route('owner.inventory.adjustments.create')">
                    Stock Adjustment
                </x-button>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif

    <!-- Filters & Search Toolbar -->
    <x-card>
        <form method="GET" action="{{ route('owner.inventory.stock.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Search Items</label>
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Search item name, SKU, barcode..."
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 pl-8 py-2"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div>
                <label for="warehouse_id" class="block text-xs font-semibold text-slate-600 mb-1">Warehouse</label>
                <select name="warehouse_id" id="warehouse_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(request('warehouse_id') === $wh->id)>{{ $wh->name }} ({{ $wh->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <x-button type="submit" variant="secondary" size="sm" class="w-full justify-center">Filter</x-button>
                @if(request()->hasAny(['search', 'warehouse_id']))
                    <a href="{{ route('owner.inventory.stock.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1.5 flex items-center">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Balances Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($balances->isEmpty())
            <x-empty-state
                title="No stock records found"
                description="{{ request()->hasAny(['search', 'warehouse_id']) ? 'No stock balances match your filter criteria.' : 'Begin by recording opening stock or receiving items through purchase orders.' }}"
                actionText="{{ request()->hasAny(['search', 'warehouse_id']) ? '' : 'Record Opening Stock' }}"
                :actionUrl="request()->hasAny(['search', 'warehouse_id']) ? null : route('owner.inventory.stock.opening_stock')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Item & SKU</th>
                            <th class="py-3 px-4">Warehouse & Location</th>
                            <th class="py-3 px-4">Tracking Mode</th>
                            <th class="py-3 px-4">Batch / Tracking</th>
                            <th class="py-3 px-4 text-right">On Hand</th>
                            <th class="py-3 px-4 text-right">In Transit</th>
                            <th class="py-3 px-4 text-right">Available</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($balances as $balance)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 block">{{ $balance->item->name }}</span>
                                    <span class="text-[11px] text-slate-500 font-mono">{{ $balance->item->sku }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-slate-800 block">{{ $balance->warehouse->name }}</span>
                                    @if($balance->location)
                                        <span class="text-[11px] text-slate-500">{{ $balance->location->name }}</span>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Default Area</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                        {{ $balance->item->inventory_mode->value }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($balance->batch)
                                        <div class="font-mono text-slate-800 font-medium">Batch #{{ $balance->batch->batch_number }}</div>
                                        @if($balance->batch->expiry_date)
                                            <span class="text-[10px] text-slate-500">Exp: {{ $balance->batch->expiry_date->format('d M Y') }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-slate-700 font-mono">
                                    {{ number_format((float) $balance->quantity_on_hand, 2) }} {{ $balance->item->unit?->code }}
                                </td>
                                <td class="py-3 px-4 text-right font-medium text-amber-600 font-mono">
                                    {{ number_format((float) $balance->quantity_in_transit, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold font-mono {{ (float) $balance->quantity_available > 0 ? 'text-emerald-700' : ((float) $balance->quantity_available < 0 ? 'text-rose-600' : 'text-slate-500') }}">
                                    {{ number_format((float) $balance->quantity_available, 2) }} {{ $balance->item->unit?->code }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($balances->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $balances->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
