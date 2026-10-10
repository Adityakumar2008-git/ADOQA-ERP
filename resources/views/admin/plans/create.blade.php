@extends('layouts.admin')

@section('title', 'New Subscription Plan')
@section('page_title', 'Create Subscription Plan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div>
        <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('admin.subscription-plans.index') }}" class="hover:text-slate-800">Subscription Plans</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">New Plan</span>
        </nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create Subscription Plan</h1>
        <p class="text-sm text-slate-500 mt-1">Configure a new pricing tier for platform tenant provisioning.</p>
    </div>

    <form action="{{ route('admin.subscription-plans.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-semibold text-slate-700">Plan Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}"
                           placeholder="e.g., Professional Business Tier"
                           class="mt-1 block w-full px-3 py-2 border @error('name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="block text-xs font-semibold text-slate-700">Plan Code / Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                           placeholder="e.g., professional-tier (auto-generated if empty)"
                           class="mt-1 block w-full px-3 py-2 border @error('slug') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] font-mono">
                    @error('slug')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="block text-xs font-semibold text-slate-700">Price (INR) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" id="price" required value="{{ old('price', '999.00') }}"
                           class="mt-1 block w-full px-3 py-2 border @error('price') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                    @error('price')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_cycle" class="block text-xs font-semibold text-slate-700">Billing Cycle <span class="text-rose-500">*</span></label>
                    <select name="billing_cycle" id="billing_cycle" required
                            class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                        <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('billing_cycle') === 'quarterly' ? 'selected' : '' }}>Quarterly (3 Months)</option>
                        <option value="yearly" {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}>Yearly (12 Months)</option>
                    </select>
                </div>

                <div>
                    <label for="trial_days" class="block text-xs font-semibold text-slate-700">Trial Period (Days)</label>
                    <input type="number" min="0" name="trial_days" id="trial_days" value="{{ old('trial_days', '0') }}"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-xs font-semibold text-slate-700">Plan Description</label>
                    <textarea name="description" id="description" rows="2"
                              placeholder="Brief description of who this plan is tailored for..."
                              class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">{{ old('description') }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="features" class="block text-xs font-semibold text-slate-700">Included Features (One per line)</label>
                    <textarea name="features" id="features" rows="4"
                              placeholder="Up to 5 Users&#10;Unlimited Invoicing&#10;Barcode Scanner Support&#10;Multi-device POS"
                              class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">{{ old('features') }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-500">Enter each feature on a separate line.</p>
                </div>

                <div class="sm:col-span-2 flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                           class="h-4 w-4 text-[#1C9640] focus:ring-[#22B14C] border-slate-300 rounded">
                    <label for="is_active" class="ml-2 block text-xs text-slate-700 font-medium">
                        Active & Available for tenant assignment
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.subscription-plans.index') }}"
               class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-[#22B14C] hover:bg-[#1E9E44] shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#22B14C] transition-colors">
                Save Subscription Plan
            </button>
        </div>
    </form>
</div>
@endsection
