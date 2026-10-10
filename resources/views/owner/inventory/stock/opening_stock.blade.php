@extends('layouts.owner')

@section('title', 'Record Opening Stock')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <x-page-header
        title="Record Opening Stock"
        subtitle="Establish baseline inventory balances for existing stock prior to running normal ERP operations."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.stock.index')">
                Back to Stock Balances
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <x-card>
        <form method="POST" action="{{ route('owner.inventory.stock.opening_stock.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="item_id" class="block text-xs font-semibold text-slate-700 mb-1">Select Item *</label>
                    <select
                        name="item_id"
                        id="item_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="">-- Choose Catalogue Item --</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" data-mode="{{ $item->inventory_mode->value }}" @selected(old('item_id') === $item->id)>
                                {{ $item->name }} (SKU: {{ $item->sku }}) [{{ $item->inventory_mode->value }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="warehouse_id" class="block text-xs font-semibold text-slate-700 mb-1">Target Warehouse *</label>
                    <select
                        name="warehouse_id"
                        id="warehouse_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="">-- Choose Warehouse --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') === $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="quantity" class="block text-xs font-semibold text-slate-700 mb-1">Opening Quantity *</label>
                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        step="any"
                        min="0.0001"
                        value="{{ old('quantity', '1') }}"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3 font-mono"
                    >
                </div>

                <div>
                    <label for="unit_cost" class="block text-xs font-semibold text-slate-700 mb-1">Valuation Unit Cost ({{ $tenant->currency }})</label>
                    <input
                        type="number"
                        name="unit_cost"
                        id="unit_cost"
                        step="0.01"
                        min="0"
                        value="{{ old('unit_cost') }}"
                        placeholder="0.00"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3 font-mono"
                    >
                </div>
            </div>

            <!-- Batch Details (Optional/Batch Mode) -->
            <div class="pt-2 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Batch Tracking (For BATCH Items)</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="batch_number" class="block text-xs font-semibold text-slate-600 mb-1">Batch Number</label>
                        <input
                            type="text"
                            name="batch_number"
                            id="batch_number"
                            value="{{ old('batch_number') }}"
                            placeholder="e.g. BATCH-2026-A"
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3 font-mono uppercase"
                        >
                    </div>
                    <div>
                        <label for="manufacturing_date" class="block text-xs font-semibold text-slate-600 mb-1">Mfg Date</label>
                        <input
                            type="date"
                            name="manufacturing_date"
                            id="manufacturing_date"
                            value="{{ old('manufacturing_date') }}"
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                        >
                    </div>
                    <div>
                        <label for="expiry_date" class="block text-xs font-semibold text-slate-600 mb-1">Expiry Date</label>
                        <input
                            type="date"
                            name="expiry_date"
                            id="expiry_date"
                            value="{{ old('expiry_date') }}"
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                        >
                    </div>
                </div>
            </div>

            <!-- Serial / IMEI Details -->
            <div class="pt-2 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="serial_numbers_text" class="block text-xs font-semibold text-slate-600 mb-1">
                        Serial Numbers (1 per line for SERIAL items)
                    </label>
                    <textarea
                        name="serial_numbers_text"
                        id="serial_numbers_text"
                        rows="3"
                        placeholder="SN-10001&#10;SN-10002"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                    >{{ old('serial_numbers_text') }}</textarea>
                </div>
                <div>
                    <label for="imeis_text" class="block text-xs font-semibold text-slate-600 mb-1">
                        IMEI Numbers (1 device per line: imei1,imei2)
                    </label>
                    <textarea
                        name="imeis_text"
                        id="imeis_text"
                        rows="3"
                        placeholder="352099001761481,352099001761482&#10;352099001761483"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                    >{{ old('imeis_text') }}</textarea>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Audit Notes</label>
                <input
                    type="text"
                    name="notes"
                    id="notes"
                    value="{{ old('notes', 'Initial stock take upon system setup') }}"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                >
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.inventory.stock.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Record Opening Stock</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
