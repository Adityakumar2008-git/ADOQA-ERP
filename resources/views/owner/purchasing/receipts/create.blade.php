@extends('layouts.owner')

@section('title', 'Receive Goods')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Receive Goods"
        subtitle="Prepare a physical stock intake document. Posting will atomically mutate stock balances and movements."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.purchasing.receipts.index')">
                Back to Receipts
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
        <form method="POST" action="{{ route('owner.purchasing.receipts.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="supplier_id" class="block text-xs font-semibold text-slate-700 mb-1">Delivering Supplier *</label>
                    <select
                        name="supplier_id"
                        id="supplier_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                        <option value="">-- Choose Vendor --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') === $s->id)>
                                {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="warehouse_id" class="block text-xs font-semibold text-slate-700 mb-1">Receiving Warehouse *</label>
                    <select
                        name="warehouse_id"
                        id="warehouse_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                        <option value="">-- Select Depot --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(old('warehouse_id') === $wh->id)>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="received_date" class="block text-xs font-semibold text-slate-700 mb-1">Receipt Date *</label>
                    <input
                        type="date"
                        name="received_date"
                        id="received_date"
                        value="{{ old('received_date', date('Y-m-d')) }}"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="purchase_order_id" class="block text-xs font-semibold text-slate-700 mb-1">Related Purchase Order (Optional)</label>
                    <select
                        name="purchase_order_id"
                        id="purchase_order_id"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 font-mono"
                    >
                        <option value="">-- Standalone Receipt --</option>
                        @foreach($orders as $o)
                            <option value="{{ $o->id }}" @selected(old('purchase_order_id', request('purchase_order_id')) === $o->id)>
                                {{ $o->order_number }} ({{ $o->supplier->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="purchase_bill_id" class="block text-xs font-semibold text-slate-700 mb-1">Related Purchase Bill (Optional)</label>
                    <select
                        name="purchase_bill_id"
                        id="purchase_bill_id"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 font-mono"
                    >
                        <option value="">-- Bill Not Yet Received / Separate --</option>
                        @foreach($bills as $b)
                            <option value="{{ $b->id }}" @selected(old('purchase_bill_id') === $b->id)>
                                {{ $b->bill_number }} (Inv: {{ $b->supplier_invoice_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Receipt Lines -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Goods Received Lines</h4>
                    <p class="text-[11px] text-slate-500">Record accepted quantities and actual purchase intake costs.</p>
                </div>

                <div class="space-y-4">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <div class="md:col-span-5">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Item *</label>
                                <select
                                    name="lines[0][item_id]"
                                    required
                                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                                >
                                    <option value="">-- Select Item --</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-cost="{{ $item->cost_price }}">
                                            {{ $item->name }} (SKU: {{ $item->sku }}) [{{ $item->inventory_mode->value }}]
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Accepted Qty *</label>
                                <input
                                    type="number"
                                    name="lines[0][accepted_quantity]"
                                    step="any"
                                    min="0.0001"
                                    value="1"
                                    required
                                    class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                                >
                            </div>

                            <div class="md:col-span-4">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Actual Unit Cost ({{ $tenant->currency }}) *</label>
                                <input
                                    type="number"
                                    name="lines[0][actual_unit_cost]"
                                    step="0.01"
                                    min="0"
                                    required
                                    placeholder="0.00"
                                    class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                                >
                            </div>
                        </div>

                        <!-- Optional Batch / Serial input for the line -->
                        <div class="pt-2 border-t border-slate-200/60 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Batch # (if BATCH item)</label>
                                <input
                                    type="text"
                                    name="lines[0][batch_number]"
                                    placeholder="Batch code"
                                    class="w-full text-xs font-mono uppercase rounded border-slate-300 py-1 px-2"
                                >
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Expiry Date</label>
                                <input
                                    type="date"
                                    name="lines[0][expiry_date]"
                                    class="w-full text-xs rounded border-slate-300 py-1 px-2"
                                >
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-0.5">Serial / IMEIs (line per item)</label>
                                <input
                                    type="text"
                                    name="lines[0][serials_text]"
                                    placeholder="Comma separated serials"
                                    class="w-full text-xs font-mono rounded border-slate-300 py-1 px-2"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Receipt Notes / Delivery Slip Reference</label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    placeholder="Challan / delivery slip number, vehicle number..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.purchasing.receipts.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Goods Receipt (Draft)</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
