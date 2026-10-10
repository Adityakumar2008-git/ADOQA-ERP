@extends('layouts.owner')

@section('title', 'New Stock Transfer')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Initiate Stock Transfer"
        subtitle="Prepare an internal goods transfer between business warehouses with strict in-transit tracking."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.transfers.index')">
                Back to Transfers
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
        <form method="POST" action="{{ route('owner.inventory.transfers.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="source_warehouse_id" class="block text-xs font-semibold text-slate-700 mb-1">Source Warehouse (Dispatch From) *</label>
                    <select
                        name="source_warehouse_id"
                        id="source_warehouse_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="">-- Select Source Depot --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('source_warehouse_id') === $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="destination_warehouse_id" class="block text-xs font-semibold text-slate-700 mb-1">Destination Warehouse (Deliver To) *</label>
                    <select
                        name="destination_warehouse_id"
                        id="destination_warehouse_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="">-- Select Destination Depot --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('destination_warehouse_id') === $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="transfer_date" class="block text-xs font-semibold text-slate-700 mb-1">Transfer Date *</label>
                    <input
                        type="date"
                        name="transfer_date"
                        id="transfer_date"
                        value="{{ old('transfer_date', date('Y-m-d')) }}"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                </div>
            </div>

            <!-- Transfer Lines Table -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Transfer Items</h4>
                    <p class="text-[11px] text-slate-500">Items and quantities to dispatch from source warehouse.</p>
                </div>

                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-8">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Item *</label>
                            <select
                                name="lines[0][item_id]"
                                required
                                class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                            >
                                <option value="">-- Select Item --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} (SKU: {{ $item->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Quantity to Transfer *</label>
                            <input
                                type="number"
                                name="lines[0][quantity]"
                                step="any"
                                min="0.0001"
                                required
                                placeholder="Qty"
                                class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Transfer Notes</label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    placeholder="Vehicle details, transfer manifest notes..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.inventory.transfers.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Transfer (Draft)</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
