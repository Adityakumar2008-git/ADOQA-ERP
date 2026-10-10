@extends('layouts.owner')

@section('title', 'Items Catalogue')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Items Catalogue"
        subtitle="Manage inventory item master records, SKU identity, and retail pricing."
    >
        <x-slot:actions>
            <x-button variant="primary" :href="route('owner.catalogue.items.create')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Item
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Filters & Search Toolbar -->
    <x-card>
        <form method="GET" action="{{ route('owner.catalogue.items.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="md:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name, SKU, barcode..."
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] pl-8 py-2"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Category -->
            <div>
                <label for="category_id" class="block text-xs font-semibold text-slate-600 mb-1">Category</label>
                <select name="category_id" id="category_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Brand -->
            <div>
                <label for="brand_id" class="block text-xs font-semibold text-slate-600 mb-1">Brand</label>
                <select name="brand_id" id="brand_id" class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C] py-2">
                    <option value="">All Brands</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(request('brand_id') === $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <x-button type="submit" variant="secondary" size="sm" class="w-full justify-center">Filter</x-button>
                @if(request()->hasAny(['search', 'category_id', 'brand_id', 'status', 'inventory_mode']))
                    <a href="{{ route('owner.catalogue.items.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1.5 flex items-center">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Items Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        @if($items->isEmpty())
            <x-empty-state
                title="No items found in catalogue"
                description="{{ request()->hasAny(['search', 'category_id', 'brand_id', 'status']) ? 'No items match your active filter criteria.' : 'Create your first item to begin populating your store catalogue.' }}"
                actionText="{{ request()->hasAny(['search', 'category_id', 'brand_id', 'status']) ? '' : 'Add First Item' }}"
                :actionUrl="request()->hasAny(['search', 'category_id', 'brand_id', 'status']) ? null : route('owner.catalogue.items.create')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-4">Item Name & Identifiers</th>
                            <th class="py-3 px-4">Classification</th>
                            <th class="py-3 px-4">Tracking Mode</th>
                            <th class="py-3 px-4 text-right">Cost Price</th>
                            <th class="py-3 px-4 text-right">Sale Price</th>
                            <th class="py-3 px-4 text-right">MRP</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    <a href="{{ route('owner.catalogue.items.show', $item) }}" class="font-bold text-slate-900 hover:text-[#1C9640] block">
                                        {{ $item->name }}
                                    </a>
                                    <div class="flex items-center space-x-2 text-[11px] text-slate-500 mt-0.5">
                                        @if($item->sku)
                                            <span class="font-mono bg-slate-100 px-1 rounded">SKU: {{ $item->sku }}</span>
                                        @endif
                                        @if($item->barcode)
                                            <span class="font-mono text-slate-400">Barcode: {{ $item->barcode }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <div class="font-medium text-slate-800">{{ $item->category?->name ?? '—' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $item->brand?->name ?? 'No brand' }} · {{ $item->unit->code }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ $item->inventory_mode->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-600">
                                    ₹{{ number_format((float) $item->cost_price, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-semibold text-slate-900">
                                    ₹{{ number_format((float) $item->sale_price, 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-slate-400">
                                    {{ $item->mrp ? '₹'.number_format((float) $item->mrp, 2) : '—' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($item->status === \App\Enums\ItemStatus::Active)
                                        <x-badge variant="emerald" size="sm">Active</x-badge>
                                    @else
                                        <x-badge variant="slate" size="sm">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('owner.catalogue.items.show', $item) }}" class="text-[#1C9640] hover:text-[#13682C] font-medium">View</a>
                                        <a href="{{ route('owner.catalogue.items.edit', $item) }}" class="text-slate-600 hover:text-slate-900">Edit</a>
                                        <form method="POST" action="{{ route('owner.catalogue.items.status', $item) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-slate-400 hover:text-slate-700 text-[11px]">
                                                {{ $item->isActive() ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $items->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
