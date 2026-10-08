@extends('layouts.owner')

@section('title', 'Edit '.$item->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Edit Item"
        subtitle="{{ $item->name }}"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.catalogue.items.show', $item)">Cancel</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('owner.catalogue.items.update', $item) }}" class="space-y-6">
        @csrf
        @method('PUT')

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
                        value="{{ old('name', $item->name) }}"
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
                        value="{{ old('sku', $item->sku) }}"
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
                        value="{{ old('barcode', $item->barcode) }}"
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
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >{{ old('description', $item->description) }}</textarea>
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
                            <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="brand_id" class="block text-xs font-semibold text-slate-700 mb-1">Brand / Manufacturer</label>
                    <select name="brand_id" id="brand_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No Brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $item->brand_id) === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-xs font-semibold text-slate-700 mb-1">Unit of Measure <span class="text-rose-500">*</span></label>
                    <select name="unit_id" id="unit_id" required class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id', $item->unit_id) === $unit->id)>
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
                            <option value="{{ $mode->value }}" @selected(old('inventory_mode', $item->inventory_mode->value) === $mode->value)>
                                {{ $mode->label() }}
                            </option>
                        @endforeach
                    </select>
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
                        value="{{ old('cost_price', $item->cost_price) }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
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
                        value="{{ old('sale_price', $item->sale_price) }}"
                        class="w-full text-xs font-mono font-bold rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
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
                        value="{{ old('mrp', $item->mrp) }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
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
                        value="{{ old('tax_rate', $item->tax_rate) }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
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
                        value="{{ old('minimum_stock_threshold', $item->minimum_stock_threshold) }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    @error('minimum_stock_threshold')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <x-button variant="secondary" :href="route('owner.catalogue.items.show', $item)">Cancel</x-button>
            <x-button type="submit" variant="primary">Save Changes</x-button>
        </div>
    </form>
</div>
@endsection
