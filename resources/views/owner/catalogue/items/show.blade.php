@extends('layouts.owner')

@section('title', $item->name.' — Item Details')

@section('content')
<div class="space-y-6">
    <x-page-header
        title="{{ $item->name }}"
        subtitle="Catalogue item master definition and commercial profile."
    >
        <x-slot:badge>
            @if($item->isActive())
                <x-badge variant="emerald">Active</x-badge>
            @else
                <x-badge variant="slate">Inactive</x-badge>
            @endif
        </x-slot:badge>

        <x-slot:actions>
            <div class="flex items-center space-x-2">
                <form method="POST" action="{{ route('owner.catalogue.items.status', $item) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <x-button type="submit" variant="secondary" size="sm">
                        {{ $item->isActive() ? 'Deactivate' : 'Activate' }}
                    </x-button>
                </form>
                <x-button variant="primary" size="sm" :href="route('owner.catalogue.items.edit', $item)">
                    Edit Item
                </x-button>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Top Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card
            label="Sale Price"
            value="₹{{ number_format((float) $item->sale_price, 2) }}"
            subtext="Standard customer checkout rate"
        />
        <x-stat-card
            label="Cost Price"
            value="₹{{ number_format((float) $item->cost_price, 2) }}"
            subtext="Baseline purchase valuation"
        />
        <x-stat-card
            label="MRP"
            value="{{ $item->mrp ? '₹'.number_format((float) $item->mrp, 2) : 'Not specified' }}"
            subtext="Maximum retail price printed on pack"
        />
        <x-stat-card
            label="Reorder Threshold"
            value="{{ number_format((float) $item->minimum_stock_threshold, 2) }} {{ $item->unit->code }}"
            subtext="Minimum safety buffer quantity"
        />
    </div>

    <!-- Main Detail Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Identity & Metadata -->
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Item Identity & Classification">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-500">Internal Name</dt>
                        <dd class="text-slate-900 font-medium mt-1">{{ $item->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">SKU</dt>
                        <dd class="text-slate-900 font-mono mt-1">{{ $item->sku ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Barcode / UPC</dt>
                        <dd class="text-slate-900 font-mono mt-1">{{ $item->barcode ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Tracking Mode</dt>
                        <dd class="text-slate-900 font-medium mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                                {{ $item->inventory_mode->label() }}
                            </span>
                            <span class="text-[11px] text-slate-400 block mt-0.5">{{ $item->inventory_mode->description() }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Category</dt>
                        <dd class="text-slate-900 font-medium mt-1">{{ $item->category?->name ?? 'None' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Brand / Manufacturer</dt>
                        <dd class="text-slate-900 font-medium mt-1">{{ $item->brand?->name ?? 'None' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Unit of Measure</dt>
                        <dd class="text-slate-900 font-medium mt-1">{{ $item->unit->name }} ({{ $item->unit->code }})</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Tax Rate (GST)</dt>
                        <dd class="text-slate-900 font-medium mt-1">{{ $item->tax_rate !== null ? number_format((float) $item->tax_rate, 2).'%' : 'Exempt / Not configured' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-slate-500">Description</dt>
                        <dd class="text-slate-700 mt-1 leading-relaxed">{{ $item->description ?? 'No description provided.' }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Future Inventory Connection Banner -->
            <div class="p-5 rounded-xl border border-dashed border-slate-200 bg-slate-50/70">
                <div class="flex items-start space-x-3">
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-xs font-bold text-slate-800">Inventory Stock & Location Ledgers</h4>
                            <x-badge variant="slate" size="xs">Phase 4 Scheduled</x-badge>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Physical stock tracking, warehouse bin allocation, IMEI/serial assignment, and stock movements are scheduled for Phase 4. This item catalogue record serves as the authoritative master definition.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions & System Info -->
        <div class="space-y-6">
            <x-card title="Record Information">
                <dl class="space-y-3 text-xs">
                    <div>
                        <dt class="font-semibold text-slate-500">ULID</dt>
                        <dd class="font-mono text-slate-700 text-[11px] mt-0.5 break-all">{{ $item->id }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Created</dt>
                        <dd class="text-slate-700 mt-0.5">{{ $item->created_at->format('M d, Y · h:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Last Modified</dt>
                        <dd class="text-slate-700 mt-0.5">{{ $item->updated_at->format('M d, Y · h:i A') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Danger Zone">
                <p class="text-xs text-slate-500 mb-3">
                    Deleting an item permanently removes it from the catalogue. If referenced by future transactions, deactivation is required instead.
                </p>
                <form method="POST" action="{{ route('owner.catalogue.items.destroy', $item) }}" onsubmit="return confirm('Are you sure you want to delete this catalogue item?');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm" class="w-full justify-center">
                        Delete Item
                    </x-button>
                </form>
            </x-card>
        </div>
    </div>
</div>
@endsection
