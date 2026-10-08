@extends('layouts.admin')

@section('title', 'Provision New Business')
@section('page_title', 'Provision Business')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div>
        <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('admin.businesses.index') }}" class="hover:text-slate-800">Businesses</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">Provision New Business</span>
        </nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Provision Business Tenant</h1>
        <p class="text-sm text-slate-500 mt-1">
            Authoritatively create an isolated business tenant, primary owner identity, canonical RBAC owner role, and optional subscription plan.
        </p>
    </div>

    <!-- Security & Isolation Callout -->
    <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-4 flex items-start text-xs text-amber-900">
        <svg class="w-4 h-4 text-amber-600 mt-0.5 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <span class="font-bold">Strict Platform Scope Isolation:</span>
            The platform administrator will NOT be added as a member of this tenant. A separate owner credential will be created exclusively for the business owner.
        </div>
    </div>

    <form action="{{ route('admin.businesses.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Section 1: Business Identity -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">1. Business Profile</h2>
                <p class="text-xs text-slate-500">Commercial profile and tenant identification.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700">Business Display Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}"
                           placeholder="e.g., Acme Retail Store"
                           class="mt-1 block w-full px-3 py-2 border @error('name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="legal_name" class="block text-xs font-semibold text-slate-700">Legal Business Name</label>
                    <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name') }}"
                           placeholder="e.g., Acme Retail Private Limited"
                           class="mt-1 block w-full px-3 py-2 border @error('legal_name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('legal_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="block text-xs font-semibold text-slate-700">Tenant Slug / URL Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="slug" id="slug" required value="{{ old('slug') }}"
                           placeholder="e.g., acme-retail"
                           class="mt-1 block w-full px-3 py-2 border @error('slug') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    <p class="mt-1 text-[11px] text-slate-500">Lowercase letters, numbers, and hyphens only. Must be unique.</p>
                    @error('slug')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="business_type" class="block text-xs font-semibold text-slate-700">Business Type</label>
                    <select name="business_type" id="business_type"
                            class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="retail" {{ old('business_type') === 'retail' ? 'selected' : '' }}>Retail Store</option>
                        <option value="wholesale" {{ old('business_type') === 'wholesale' ? 'selected' : '' }}>Wholesale / Distribution</option>
                        <option value="service" {{ old('business_type') === 'service' ? 'selected' : '' }}>Service Provider</option>
                        <option value="electronics" {{ old('business_type') === 'electronics' ? 'selected' : '' }}>Mobile & Electronics</option>
                        <option value="apparel" {{ old('business_type') === 'apparel' ? 'selected' : '' }}>Apparel & Footwear</option>
                        <option value="general" {{ old('business_type') === 'general' ? 'selected' : '' }}>General Commercial</option>
                    </select>
                    @error('business_type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700">Business Contact Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           placeholder="store@acme.local"
                           class="mt-1 block w-full px-3 py-2 border @error('email') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700">Business Contact Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                           placeholder="+919876543210"
                           class="mt-1 block w-full px-3 py-2 border @error('phone') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 2: Location, Timezone, Currency, Tax -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">2. Location & Financial Configuration</h2>
                <p class="text-xs text-slate-500">Address, tax numbering, operating currency, and local timezone.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="address_line_1" class="block text-xs font-semibold text-slate-700">Address Line 1</label>
                    <input type="text" name="address_line_1" id="address_line_1" value="{{ old('address_line_1') }}"
                           placeholder="Shop 12, Ground Floor, Central Mall"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="city" class="block text-xs font-semibold text-slate-700">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}"
                           placeholder="Bengaluru"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="state" class="block text-xs font-semibold text-slate-700">State / Province</label>
                    <input type="text" name="state" id="state" value="{{ old('state') }}"
                           placeholder="Karnataka"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="postal_code" class="block text-xs font-semibold text-slate-700">Postal / PIN Code</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                           placeholder="560001"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="country" class="block text-xs font-semibold text-slate-700">Country Code</label>
                    <input type="text" name="country" id="country" value="{{ old('country', 'IN') }}"
                           placeholder="IN"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>

                <div>
                    <label for="currency" class="block text-xs font-semibold text-slate-700">Currency Code</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency', 'INR') }}"
                           placeholder="INR"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>

                <div>
                    <label for="timezone" class="block text-xs font-semibold text-slate-700">Timezone</label>
                    <input type="text" name="timezone" id="timezone" value="{{ old('timezone', 'Asia/Kolkata') }}"
                           placeholder="Asia/Kolkata"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="tax_number" class="block text-xs font-semibold text-slate-700">Tax / GST Number</label>
                    <input type="text" name="tax_number" id="tax_number" value="{{ old('tax_number') }}"
                           placeholder="e.g., 29ABCDE1234F1Z5"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
                </div>
            </div>
        </div>

        <!-- Section 3: Owner Identity & Initial Credentials -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">3. Business Owner Identity</h2>
                <p class="text-xs text-slate-500">The primary human owner for this business. Each ordinary account belongs to exactly one business.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="owner_name" class="block text-xs font-semibold text-slate-700">Owner Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="owner_name" id="owner_name" required value="{{ old('owner_name') }}"
                           placeholder="e.g., Rajesh Sharma"
                           class="mt-1 block w-full px-3 py-2 border @error('owner_name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('owner_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="owner_email" class="block text-xs font-semibold text-slate-700">Owner Email Address <span class="text-rose-500">*</span></label>
                    <input type="email" name="owner_email" id="owner_email" required value="{{ old('owner_email') }}"
                           placeholder="owner@acmeretail.local"
                           class="mt-1 block w-full px-3 py-2 border @error('owner_email') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('owner_email')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="owner_phone" class="block text-xs font-semibold text-slate-700">Owner Phone Number</label>
                    <input type="text" name="owner_phone" id="owner_phone" value="{{ old('owner_phone') }}"
                           placeholder="+919876543210"
                           class="mt-1 block w-full px-3 py-2 border @error('owner_phone') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('owner_phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="owner_password" class="block text-xs font-semibold text-slate-700">Initial Secure Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="owner_password" id="owner_password" required
                           placeholder="Minimum 8 characters"
                           class="mt-1 block w-full px-3 py-2 border @error('owner_password') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('owner_password')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 4: Subscription Plan Assignment -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">4. Initial Subscription Plan (Optional)</h2>
                    <p class="text-xs text-slate-500">Select an existing subscription plan to provision alongside the business tenant.</p>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                    Offline Billing
                </span>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="plan_id" class="block text-xs font-semibold text-slate-700">Subscription Plan</label>
                    <select name="plan_id" id="plan_id"
                            class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">No Subscription Plan (Assign Later)</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id') === $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — ₹{{ number_format((float) $plan->price, 2) }} / {{ $plan->billing_cycle->label() }}
                                @if ($plan->trial_days > 0)
                                    ({{ $plan->trial_days }} days trial)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="skip_trial" id="skip_trial" value="1" {{ old('skip_trial') ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
                    <label for="skip_trial" class="ml-2 block text-xs text-slate-700 font-medium">
                        Skip trial period (activate subscription directly without trial status)
                    </label>
                </div>

                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-[11px] text-slate-500">
                    <span class="font-semibold text-slate-700">Manual Billing Policy:</span>
                    Plan prices are administrative references. ADOQA collects subscription dues offline. No automated gateway charge or card transaction will be triggered.
                </div>
            </div>
        </div>

        <!-- Form Action Toolbar -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.businesses.index') }}"
               class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" id="provision-business-btn"
                    class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Provision Business Tenant
            </button>
        </div>
    </form>
</div>
@endsection
