@extends('layouts.owner')

@section('title', 'New Unit')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <x-page-header
        title="Create Unit of Measure"
        subtitle="Define a measurement unit for your catalogue items."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.catalogue.units.index')">Cancel</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <form method="POST" action="{{ route('owner.catalogue.units.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Unit Full Name <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    required
                    value="{{ old('name') }}"
                    placeholder="e.g. Kilogram, Piece, Bag, Meter, Pack..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C]"
                >
                @error('name')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="code" class="block text-xs font-semibold text-slate-700 mb-1">Unit Code / Symbol <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    name="code"
                    id="code"
                    required
                    value="{{ old('code') }}"
                    placeholder="e.g. KG, PCS, BAG, MTR, PKT..."
                    class="w-full text-xs font-mono uppercase rounded-lg border-slate-300 shadow-2xs focus:border-[#22B14C] focus:ring-[#22B14C]"
                >
                <p class="text-[11px] text-slate-400 mt-1">Short standardized abbreviation shown on invoices and receipts.</p>
                @error('code')<p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                <x-button variant="secondary" :href="route('owner.catalogue.units.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Unit</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
