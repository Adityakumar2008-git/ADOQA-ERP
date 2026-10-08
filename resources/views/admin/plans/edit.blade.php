@extends('layouts.admin')

@section('title', 'Edit ' . $plan->name)
@section('page_title', 'Edit Subscription Plan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div>
        <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('admin.subscription-plans.index') }}" class="hover:text-slate-800">Subscription Plans</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">Edit {{ $plan->name }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Plan: {{ $plan->name }}</h1>
                <p class="text-xs text-slate-400 font-mono mt-1">Plan Code: {{ $plan->slug }} &bull; Subscribers: {{ $plan->subscriptions()->count() }}</p>
            </div>
            @if ($plan->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Active
                </span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                    Inactive
                </span>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.subscription-plans.update', $plan) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-xs font-semibold text-slate-700">Plan Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $plan->name) }}"
                           class="mt-1 block w-full px-3 py-2 border @error('name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="block text-xs font-semibold text-slate-700">Price (INR) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" id="price" required value="{{ old('price', (string) $plan->price) }}"
                           class="mt-1 block w-full px-3 py-2 border @error('price') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('price')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="billing_cycle" class="block text-xs font-semibold text-slate-700">Billing Cycle <span class="text-rose-500">*</span></label>
                    <select name="billing_cycle" id="billing_cycle" required
                            class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle->value) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('billing_cycle', $plan->billing_cycle->value) === 'quarterly' ? 'selected' : '' }}>Quarterly (3 Months)</option>
                        <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle->value) === 'yearly' ? 'selected' : '' }}>Yearly (12 Months)</option>
                    </select>
                </div>

                <div>
                    <label for="trial_days" class="block text-xs font-semibold text-slate-700">Trial Period (Days)</label>
                    <input type="number" min="0" name="trial_days" id="trial_days" value="{{ old('trial_days', $plan->trial_days) }}"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-xs font-semibold text-slate-700">Plan Description</label>
                    <textarea name="description" id="description" rows="2"
                              class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $plan->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="features" class="block text-xs font-semibold text-slate-700">Included Features (One per line)</label>
                    @php
                        $featuresText = is_array($plan->features) ? implode("\n", $plan->features) : '';
                    @endphp
                    <textarea name="features" id="features" rows="4"
                              class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('features', $featuresText) }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-500">Enter each feature on a separate line.</p>
                </div>

                <div class="sm:col-span-2 flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
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
                    class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Update Subscription Plan
            </button>
        </div>
    </form>
</div>
@endsection
