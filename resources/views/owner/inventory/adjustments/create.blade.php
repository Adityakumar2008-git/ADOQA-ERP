@extends('layouts.owner')

@section('title', 'New Stock Adjustment')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Record Stock Adjustment"
        subtitle="Compare actual physical stock count against current system projection and post reconciled adjustment."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.adjustments.index')">
                Back to Adjustments
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
        <form method="POST" action="{{ route('owner.inventory.adjustments.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

                <div>
                    <label for="adjustment_date" class="block text-xs font-semibold text-slate-700 mb-1">Audit Date *</label>
                    <input
                        type="date"
                        name="adjustment_date"
                        id="adjustment_date"
                        value="{{ old('adjustment_date', date('Y-m-d')) }}"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                </div>

                <div>
                    <label for="reason" class="block text-xs font-semibold text-slate-700 mb-1">Reason for Adjustment *</label>
                    <select
                        name="reason"
                        id="reason"
                        required
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                    >
                        <option value="Periodic physical stock count">Periodic physical stock count</option>
                        <option value="Damaged / broken items">Damaged / broken items</option>
                        <option value="Theft or shrinkage">Theft or shrinkage</option>
                        <option value="Expired stock write-off">Expired stock write-off</option>
                        <option value="Found unrecorded stock">Found unrecorded stock</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <!-- Adjustment Lines Table -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Adjustment Lines</h4>
                    <p class="text-[11px] text-slate-500">Specify counted actual physical quantity on shelf.</p>
                </div>

                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Item *</label>
                            <select
                                name="lines[0][item_id]"
                                required
                                class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                            >
                                <option value="">-- Choose Catalogue Item --</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} (SKU: {{ $item->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Counted Physical Quantity *</label>
                            <input
                                type="number"
                                name="lines[0][counted_quantity]"
                                step="any"
                                min="0"
                                required
                                placeholder="Actual count"
                                class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                            >
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Line Notes</label>
                            <input
                                type="text"
                                name="lines[0][notes]"
                                placeholder="Shelf recount verification"
                                class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-1.5 px-3"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Overall Notes</label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="2"
                    placeholder="General inspection comments..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2 px-3"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.inventory.adjustments.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Post Stock Adjustment</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
