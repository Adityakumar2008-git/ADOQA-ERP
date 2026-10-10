@extends('layouts.owner')

@section('title', 'Stock Movement Ledger')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Stock Movement Ledger"
        subtitle="Append-only immutable record of all physical inventory changes and transaction audits."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.stock.index')">
                Current Balances
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('owner.inventory.stock.ledger') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Search Items</label>
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Search item or SKU..."
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] pl-8 py-2"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div>
                <label for="warehouse_id" class="block text-xs font-semibold text-slate-600 mb-1">Warehouse</label>
                <select name="warehouse_id" id="warehouse_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @selected(request('warehouse_id') === $wh->id)>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="movement_type" class="block text-xs font-semibold text-slate-600 mb-1">Movement Type</label>
                <select name="movement_type" id="movement_type" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2">
                    <option value="">All Types</option>
                    <option value="opening_balance" @selected(request('movement_type') === 'opening_balance')>Opening Balance</option>
                    <option value="purchase_receipt" @selected(request('movement_type') === 'purchase_receipt')>Purchase Receipt</option>
                    <option value="stock_adjustment" @selected(request('movement_type') === 'stock_adjustment')>Stock Adjustment</option>
                    <option value="transfer_in" @selected(request('movement_type') === 'transfer_in')>Transfer In</option>
                    <option value="transfer_out" @selected(request('movement_type') === 'transfer_out')>Transfer Out</option>
                    <option value="damage" @selected(request('movement_type') === 'damage')>Damage</option>
                    <option value="loss" @selected(request('movement_type') === 'loss')>Loss</option>
                    <option value="reversal" @selected(request('movement_type') === 'reversal')>Reversal</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <x-button type="submit" variant="secondary" size="sm" class="w-full justify-center">Filter</x-button>
                @if(request()->hasAny(['search', 'warehouse_id', 'movement_type']))
                    <a href="{{ route('owner.inventory.stock.ledger') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1.5 flex items-center">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Movements Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($movements->isEmpty())
            <x-empty-state
                title="No stock movements recorded"
                description="All ledger transactions generated from opening stock, receipts, and transfers appear here."
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Item & SKU</th>
                            <th class="py-3 px-4">Warehouse</th>
                            <th class="py-3 px-4">Movement Type</th>
                            <th class="py-3 px-4 text-right">Quantity</th>
                            <th class="py-3 px-4 text-right">Unit Cost</th>
                            <th class="py-3 px-4">Tracking Reference</th>
                            <th class="py-3 px-4">Actor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($movements as $m)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 text-slate-600 whitespace-nowrap font-mono text-[11px]">
                                    {{ $m->occurred_at ? $m->occurred_at->format('Y-m-d H:i:s') : $m->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 block">{{ $m->item->name }}</span>
                                    <span class="text-[11px] text-slate-500 font-mono">{{ $m->item->sku }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-800 font-medium">
                                    {{ $m->warehouse->name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        @if(in_array($m->movement_type->value, ['opening_balance', 'purchase_receipt', 'transfer_in'])) bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif(in_array($m->movement_type->value, ['transfer_out', 'damage', 'loss'])) bg-rose-50 text-rose-700 border border-rose-200
                                        @else bg-sky-50 text-sky-700 border border-sky-200 @endif">
                                        {{ str_replace('_', ' ', $m->movement_type->value) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-sm {{ (float) $m->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ (float) $m->quantity > 0 ? '+' : '' }}{{ number_format((float) $m->quantity, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-700">
                                    {{ $m->unit_cost !== null ? $tenant->currency . ' ' . number_format((float) $m->unit_cost, 2) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    @if($m->batch)
                                        <div class="font-mono text-[11px]">Batch: {{ $m->batch->batch_number }}</div>
                                    @endif
                                    @if($m->unit)
                                        <div class="font-mono text-[11px] text-[#1C9640]">
                                            {{ $m->unit->serial_number ? 'S/N: ' . $m->unit->serial_number : 'IMEI: ' . $m->unit->imei1 }}
                                        </div>
                                    @endif
                                    @if($m->notes)
                                        <div class="text-[10px] text-slate-400 truncate max-w-xs" title="{{ $m->notes }}">{{ $m->notes }}</div>
                                    @endif
                                    @if(!$m->batch && !$m->unit && !$m->notes)
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-[11px]">
                                    {{ $m->actor?->name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $movements->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
