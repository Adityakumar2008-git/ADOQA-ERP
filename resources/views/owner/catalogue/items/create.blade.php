@extends('layouts.owner')

@section('title', 'New Catalogue Item')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Create New Item"
        subtitle="Define identity, classification, pricing, and future inventory behavior."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.catalogue.items.index')">Cancel</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('owner.catalogue.items.store') }}" class="space-y-6">
        @csrf

        <!-- 1. Identity -->
        <x-card title="Item Identity" subtitle="Core identification attributes and physical scanning codes.">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Item Name <span class="text-rose-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        required
                        value="{{ old('name') }}"
                        placeholder="e.g. Ultra Slim Wireless Keyboard, Cotton Shirt M, iPhone 16 Pro..."
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    @error('name')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="sku" class="block text-xs font-semibold text-slate-700 mb-1">SKU (Stock Keeping Unit)</label>
                    <input
                        type="text"
                        name="sku"
                        id="sku"
                        value="{{ old('sku') }}"
                        placeholder="e.g. KB-WL-01"
                        class="w-full text-xs font-mono uppercase rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Unique identifier within your store business.</p>
                    @error('sku')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="barcode" class="block text-xs font-semibold text-slate-700 mb-1">Barcode / EAN / UPC</label>
                    <input
                        type="text"
                        name="barcode"
                        id="barcode"
                        value="{{ old('barcode') }}"
                        placeholder="e.g. 8901234567890"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Used for fast point-of-sale scanner lookup.</p>
                    @error('barcode')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="2"
                        placeholder="Product specifications, dimensions, notes..."
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ old('description') }}</textarea>
                    @error('description')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <!-- 2. Classification & Inventory Mode -->
        <x-card title="Classification & Tracking" subtitle="Category, manufacturer brand, and unit of measure.">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
                    <select name="category_id" id="category_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="brand_id" class="block text-xs font-semibold text-slate-700 mb-1">Brand / Manufacturer</label>
                    <select name="brand_id" id="brand_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No Brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-xs font-semibold text-slate-700 mb-1">Unit of Measure <span class="text-rose-500">*</span></label>
                    <select name="unit_id" id="unit_id" required class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id') === $unit->id || (empty(old('unit_id')) && $unit->code === 'PCS'))>
                                {{ $unit->name }} ({{ $unit->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="inventory_mode" class="block text-xs font-semibold text-slate-700 mb-1">Inventory Tracking Mode <span class="text-rose-500">*</span></label>
                    <select name="inventory_mode" id="inventory_mode" required class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(\App\Enums\InventoryMode::cases() as $mode)
                            <option value="{{ $mode->value }}" @selected(old('inventory_mode', 'normal') === $mode->value)>
                                {{ $mode->label() }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Describes physical stock handling (Normal, Batch, Serial, IMEI).</p>
                    @error('inventory_mode')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <!-- 3. Pricing -->
        <x-card title="Commercial Pricing" subtitle="Purchase valuation, retail selling price, and maximum retail price.">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="cost_price" class="block text-xs font-semibold text-slate-700 mb-1">Cost Price (₹) <span class="text-rose-500">*</span></label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="cost_price"
                        id="cost_price"
                        required
                        value="{{ old('cost_price', '0.00') }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Default purchase / baseline cost.</p>
                    @error('cost_price')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="sale_price" class="block text-xs font-semibold text-slate-700 mb-1">Sale Price (₹) <span class="text-rose-500">*</span></label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="sale_price"
                        id="sale_price"
                        required
                        value="{{ old('sale_price', '0.00') }}"
                        class="w-full text-xs font-mono font-bold rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Standard selling price at checkout.</p>
                    @error('sale_price')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="mrp" class="block text-xs font-semibold text-slate-700 mb-1">MRP (₹)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="mrp"
                        id="mrp"
                        value="{{ old('mrp') }}"
                        placeholder="Printed retail price"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Cannot be lower than Sale Price.</p>
                    @error('mrp')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <!-- 4. Tax & Stock Threshold -->
        <x-card title="Tax & Reorder Threshold" subtitle="Applicable GST rate and minimum stock alert trigger.">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tax_rate" class="block text-xs font-semibold text-slate-700 mb-1">Tax Rate (%)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        name="tax_rate"
                        id="tax_rate"
                        value="{{ old('tax_rate', '18.00') }}"
                        placeholder="e.g. 18.00, 5.00, 0.00"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Item-level tax percentage applied during checkout.</p>
                    @error('tax_rate')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="minimum_stock_threshold" class="block text-xs font-semibold text-slate-700 mb-1">Minimum Stock Alert Threshold</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="minimum_stock_threshold"
                        id="minimum_stock_threshold"
                        value="{{ old('minimum_stock_threshold', '0.00') }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Threshold for low-stock triggers in future Phase 4 inventory.</p>
                    @error('minimum_stock_threshold')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <x-button variant="secondary" :href="route('owner.catalogue.items.index')">Cancel</x-button>
            <x-button type="submit" variant="primary">Create Item</x-button>
        </div>
    </form>
</div>
@endsection
