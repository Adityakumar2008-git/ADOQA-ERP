@extends('layouts.owner')

@section('title', 'New Warehouse')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <x-page-header
        title="Create Warehouse"
        subtitle="Provision a storage facility or retail stock depot for inventory operations."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.inventory.warehouses.index')">
                Back to Warehouses
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
        <form method="POST" action="{{ route('owner.inventory.warehouses.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Warehouse Name *</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    placeholder="e.g. Central Depot, Shop Backroom"
                    required
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >
            </div>

            <div>
                <label for="code" class="block text-xs font-semibold text-slate-700 mb-1">Warehouse Code</label>
                <input
                    type="text"
                    name="code"
                    id="code"
                    value="{{ old('code') }}"
                    placeholder="e.g. MAIN, DEPOT-01"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 font-mono uppercase"
                >
                <p class="text-[11px] text-slate-500 mt-1">Leave blank to automatically generate from name.</p>
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Physical Address / Notes</label>
                <textarea
                    name="address"
                    id="address"
                    rows="3"
                    placeholder="Full facility address, floor, or operational notes"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >{{ old('address') }}</textarea>
            </div>

            <div class="pt-2">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_default"
                        value="1"
                        @checked(old('is_default'))
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span class="text-xs text-slate-700 font-medium">Set as default warehouse for this business</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.inventory.warehouses.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Warehouse</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
