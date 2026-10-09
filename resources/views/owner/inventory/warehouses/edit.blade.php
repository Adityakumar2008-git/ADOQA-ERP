@extends('layouts.owner')

@section('title', 'Edit Warehouse: ' . $warehouse->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <x-page-header
        title="Configure Warehouse: {{ $warehouse->name }}"
        subtitle="Manage warehouse details, operational status, and internal picking locations."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.warehouses.index')">
                Back to Warehouses
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Edit Details Form -->
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Warehouse Properties">
                <form method="POST" action="{{ route('owner.inventory.warehouses.update', $warehouse) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Warehouse Name *</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $warehouse->name) }}"
                            required
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="code" class="block text-xs font-semibold text-slate-700 mb-1">Warehouse Code *</label>
                            <input
                                type="text"
                                name="code"
                                id="code"
                                value="{{ old('code', $warehouse->code) }}"
                                required
                                class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 font-mono uppercase"
                            >
                        </div>
                        <div>
                            <label for="status" class="block text-xs font-semibold text-slate-700 mb-1">Status *</label>
                            <select
                                name="status"
                                id="status"
                                class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                            >
                                <option value="active" @selected(old('status', $warehouse->status->value) === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', $warehouse->status->value) === 'inactive')>Inactive (Cannot receive stock)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Physical Address / Notes</label>
                        <textarea
                            name="address"
                            id="address"
                            rows="3"
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                        >{{ old('address', $warehouse->address) }}</textarea>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="is_default"
                                value="1"
                                @checked(old('is_default', $warehouse->is_default))
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="text-xs text-slate-700 font-medium">Default warehouse for business</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                        <x-button type="submit" variant="primary">Save Changes</x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Locations Management -->
        <div class="space-y-6">
            <x-card title="Internal Locations">
                <p class="text-xs text-slate-500 mb-4">
                    Optional sub-locations (e.g. Counter, Shelf A, Bin 3) inside this warehouse.
                </p>

                <!-- New Location Form -->
                <form method="POST" action="{{ route('owner.inventory.warehouses.locations.store', $warehouse) }}" class="space-y-3 pb-4 mb-4 border-b border-slate-100">
                    @csrf
                    <div>
                        <input
                            type="text"
                            name="name"
                            placeholder="Location name (e.g. Counter)"
                            required
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3"
                        >
                    </div>
                    <div>
                        <input
                            type="text"
                            name="code"
                            placeholder="Code (optional, e.g. CNTR)"
                            class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3 uppercase font-mono"
                        >
                    </div>
                    <x-button type="submit" variant="secondary" size="sm" class="w-full justify-center">
                        Add Location
                    </x-button>
                </form>

                <!-- Existing Locations List -->
                <div class="space-y-2">
                    @forelse($warehouse->locations as $location)
                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg text-xs">
                            <div>
                                <span class="font-semibold text-slate-800">{{ $location->name }}</span>
                                <span class="text-[10px] text-slate-500 font-mono block">{{ $location->code }}</span>
                            </div>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $location->status->value === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $location->status->value }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic text-center py-2">No sub-locations defined. Stock defaults to the warehouse level.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
