@extends('layouts.owner')

@section('title', 'New Purchase Bill')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Create Purchase Bill"
        subtitle="Record vendor invoice details. Posting this bill creates an official supplier payable in the subledger."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.purchasing.bills.index')">
                Back to Bills
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
        <form method="POST" action="{{ route('owner.purchasing.bills.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="supplier_id" class="block text-xs font-semibold text-slate-700 mb-1">Supplier *</label>
                    <select
                        name="supplier_id"
                        id="supplier_id"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                        <option value="">-- Choose Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') === $s->id)>
                                {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="supplier_invoice_number" class="block text-xs font-semibold text-slate-700 mb-1">Supplier Invoice / Bill # *</label>
                    <input
                        type="text"
                        name="supplier_invoice_number"
                        id="supplier_invoice_number"
                        value="{{ old('supplier_invoice_number') }}"
                        required
                        placeholder="e.g. INV-2026-904"
                        class="w-full text-xs font-mono uppercase rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>

                <div>
                    <label for="purchase_order_id" class="block text-xs font-semibold text-slate-700 mb-1">Related Purchase Order (Optional)</label>
                    <select
                        name="purchase_order_id"
                        id="purchase_order_id"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 font-mono"
                    >
                        <option value="">-- None / Standalone Bill --</option>
                        @foreach($orders as $o)
                            <option value="{{ $o->id }}" @selected(old('purchase_order_id', request('purchase_order_id')) === $o->id)>
                                {{ $o->order_number }} ({{ $o->supplier->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="bill_date" class="block text-xs font-semibold text-slate-700 mb-1">Bill Date *</label>
                    <input
                        type="date"
                        name="bill_date"
                        id="bill_date"
                        value="{{ old('bill_date', date('Y-m-d')) }}"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>

                <div>
                    <label for="due_date" class="block text-xs font-semibold text-slate-700 mb-1">Payment Due Date</label>
                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        value="{{ old('due_date') }}"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>
            </div>

            <!-- Bill Lines -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Invoiced Items</h4>
                    <p class="text-[11px] text-slate-500">Billed quantities and unit cost.</p>
                </div>

                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Item *</label>
                            <select
                                name="lines[0][item_id]"
                                required
                                class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                            >
                                <option value="">-- Choose Item --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-cost="{{ $item->cost_price }}">
                                        {{ $item->name }} (SKU: {{ $item->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Billed Qty *</label>
                            <input
                                type="number"
                                name="lines[0][quantity]"
                                step="any"
                                min="0.0001"
                                value="1"
                                required
                                class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                            >
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unit Cost ({{ $tenant->currency }}) *</label>
                            <input
                                type="number"
                                name="lines[0][unit_cost]"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                                class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Billing Notes</label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    placeholder="Invoice terms, remarks..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.purchasing.bills.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Bill (Draft)</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
