@extends('layouts.owner')

@section('title', 'New Supplier')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <x-page-header
        title="Create Supplier"
        subtitle="Register a new procurement vendor and configure billing terms."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('owner.purchasing.suppliers.index')">
                Back to Suppliers
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
        <form method="POST" action="{{ route('owner.purchasing.suppliers.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Supplier / Vendor Name *</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="e.g. Apex Electronics Ltd"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-700 mb-1">Vendor Code</label>
                    <input
                        type="text"
                        name="code"
                        id="code"
                        value="{{ old('code') }}"
                        placeholder="e.g. SUP-001"
                        class="w-full text-xs font-mono uppercase rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                    <p class="text-[11px] text-slate-500 mt-1">Leave empty to auto-generate.</p>
                </div>

                <div>
                    <label for="gst_number" class="block text-xs font-semibold text-slate-700 mb-1">GSTIN / Tax ID</label>
                    <input
                        type="text"
                        name="gst_number"
                        id="gst_number"
                        value="{{ old('gst_number') }}"
                        placeholder="22AAAAA0000A1Z5"
                        class="w-full text-xs font-mono uppercase rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone</label>
                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        value="{{ old('phone') }}"
                        placeholder="+91 98765 43210"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        placeholder="orders@vendor.com"
                        class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="credit_limit" class="block text-xs font-semibold text-slate-700 mb-1">Credit Limit ({{ $tenant->currency }})</label>
                    <input
                        type="number"
                        name="credit_limit"
                        id="credit_limit"
                        step="0.01"
                        min="0"
                        value="{{ old('credit_limit') }}"
                        placeholder="0.00"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>

                <div>
                    <label for="opening_balance" class="block text-xs font-semibold text-slate-700 mb-1">Opening Payable Balance ({{ $tenant->currency }})</label>
                    <input
                        type="number"
                        name="opening_balance"
                        id="opening_balance"
                        step="0.01"
                        value="{{ old('opening_balance', '0.00') }}"
                        class="w-full text-xs font-mono rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                    >
                </div>
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Address / Dispatch Center</label>
                <textarea
                    name="address"
                    id="address"
                    rows="2"
                    placeholder="Billing and factory address"
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >{{ old('address') }}</textarea>
            </div>

            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1">Supplier Notes</label>
                <input
                    type="text"
                    name="notes"
                    id="notes"
                    value="{{ old('notes') }}"
                    placeholder="Bank account terms, discount agreement..."
                    class="w-full text-xs rounded-lg border-slate-300 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3"
                >
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <x-button variant="secondary" :href="route('owner.purchasing.suppliers.index')">Cancel</x-button>
                <x-button type="submit" variant="primary">Create Supplier</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
