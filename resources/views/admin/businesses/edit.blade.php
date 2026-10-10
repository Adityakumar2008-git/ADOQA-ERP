@extends('layouts.admin')

@section('title', 'Edit ' . $business->name . ' — Tenant Profile')
@section('page_title', 'Edit Business')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div>
        <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-2">
            <a href="{{ route('admin.businesses.index') }}" class="hover:text-slate-800">Businesses</a>
            <span>/</span>
            <a href="{{ route('admin.businesses.show', $business) }}" class="hover:text-slate-800">{{ $business->name }}</a>
            <span>/</span>
            <span class="text-slate-800 font-medium">Edit Profile & Logo</span>
        </nav>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Business Profile & Logo</h1>
        <p class="text-sm text-slate-500 mt-1">
            Update commercial profile, contact information, address details, and brand logo for <span class="font-semibold text-slate-700">{{ $business->name }}</span>.
        </p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.businesses.update', $business) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Section 1: Business Identity & Logo -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">1. Commercial Identity & Logo</h2>
                <p class="text-xs text-slate-500">Commercial profile and tenant branding.</p>
            </div>

            <!-- Current Logo Display & Brand Logo Upload -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Brand Logo</label>
                <div class="flex items-start space-x-5 p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                    <div id="logo-preview-box" class="w-20 h-20 rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center bg-white overflow-hidden flex-shrink-0 shadow-2xs">
                        @if($business->logo_url)
                            <img id="logo-preview-img" src="{{ $business->logo_url }}" alt="{{ $business->name }} logo" class="w-full h-full object-contain">
                            <svg id="logo-placeholder-svg" class="w-8 h-8 text-slate-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @else
                            <img id="logo-preview-img" src="#" alt="Logo Preview" class="w-full h-full object-contain hidden">
                            <div id="logo-placeholder-svg" class="flex flex-col items-center justify-center text-slate-400">
                                <span class="font-bold text-lg text-slate-500">{{ strtoupper(substr($business->name, 0, 1)) }}</span>
                                <span class="text-[9px] uppercase tracking-wider text-slate-400">No Logo</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 space-y-3">
                        <div>
                            <label for="logo" class="block text-xs font-semibold text-slate-700 mb-1">
                                {{ $business->logo_path ? 'Replace Logo Image' : 'Upload Logo Image' }}
                            </label>
                            <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                   class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#E8F8EE] file:text-[#0E5C25] hover:file:bg-[#D5F3DF]">
                            <p class="text-[11px] text-slate-500 mt-1">Supported formats: PNG, JPG, JPEG, WebP, SVG. Max 2MB.</p>
                            @error('logo')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($business->logo_path)
                            <div class="pt-2 border-t border-slate-200">
                                <label class="inline-flex items-center text-xs text-rose-700 font-semibold cursor-pointer">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 mr-2">
                                    Remove current logo and restore default initial avatar
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700">Business Display Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $business->name) }}"
                           placeholder="e.g., Acme Retail Store"
                           class="mt-1 block w-full px-3 py-2 border @error('name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="legal_name" class="block text-xs font-semibold text-slate-700">Legal Business Name</label>
                    <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $business->legal_name) }}"
                           placeholder="e.g., Acme Retail Private Limited"
                           class="mt-1 block w-full px-3 py-2 border @error('legal_name') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                    @error('legal_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500">Tenant Slug / URL Code</label>
                    <input type="text" disabled value="{{ $business->slug }}"
                           class="mt-1 block w-full px-3 py-2 border border-slate-200 bg-slate-100 rounded-lg text-xs text-slate-500 font-mono cursor-not-allowed">
                    <p class="mt-1 text-[11px] text-slate-400">Canonical tenant slug is immutable.</p>
                </div>

                <div>
                    <label for="business_type" class="block text-xs font-semibold text-slate-700">Business Type</label>
                    <select name="business_type" id="business_type"
                            class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                        <option value="retail" {{ old('business_type', $business->business_type) === 'retail' ? 'selected' : '' }}>Retail Store</option>
                        <option value="wholesale" {{ old('business_type', $business->business_type) === 'wholesale' ? 'selected' : '' }}>Wholesale / Distribution</option>
                        <option value="service" {{ old('business_type', $business->business_type) === 'service' ? 'selected' : '' }}>Service Provider</option>
                        <option value="electronics" {{ old('business_type', $business->business_type) === 'electronics' ? 'selected' : '' }}>Mobile & Electronics</option>
                        <option value="apparel" {{ old('business_type', $business->business_type) === 'apparel' ? 'selected' : '' }}>Apparel & Footwear</option>
                        <option value="general" {{ old('business_type', $business->business_type) === 'general' ? 'selected' : '' }}>General Commercial</option>
                    </select>
                    @error('business_type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700">Business Contact Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $business->email) }}"
                           placeholder="store@acme.local"
                           class="mt-1 block w-full px-3 py-2 border @error('email') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-semibold text-slate-700">Business Contact Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $business->phone) }}"
                           placeholder="+919876543210"
                           class="mt-1 block w-full px-3 py-2 border @error('phone') border-rose-300 @else border-slate-300 @enderror rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 2: Location, Timezone, Currency, Tax -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-2xs space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900">2. Address & Financial Settings</h2>
                <p class="text-xs text-slate-500">Operating base and taxation identifiers.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="address_line_1" class="block text-xs font-semibold text-slate-700">Address Line 1</label>
                    <input type="text" name="address_line_1" id="address_line_1" value="{{ old('address_line_1', $business->address_line_1) }}"
                           placeholder="Building, street, premises"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                </div>

                <div class="sm:col-span-2">
                    <label for="address_line_2" class="block text-xs font-semibold text-slate-700">Address Line 2</label>
                    <input type="text" name="address_line_2" id="address_line_2" value="{{ old('address_line_2', $business->address_line_2) }}"
                           placeholder="Landmark, suite, unit"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                </div>

                <div>
                    <label for="city" class="block text-xs font-semibold text-slate-700">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $business->city) }}"
                           placeholder="e.g., Mumbai"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                </div>

                <div>
                    <label for="state" class="block text-xs font-semibold text-slate-700">State / Province</label>
                    <input type="text" name="state" id="state" value="{{ old('state', $business->state) }}"
                           placeholder="e.g., Maharashtra"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
                </div>

                <div>
                    <label for="postal_code" class="block text-xs font-semibold text-slate-700">Postal / PIN Code</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $business->postal_code) }}"
                           placeholder="e.g., 400001"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] font-mono">
                </div>

                <div>
                    <label for="country" class="block text-xs font-semibold text-slate-700">Country Code (ISO-2)</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $business->country) }}"
                           placeholder="IN" maxlength="2"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] font-mono uppercase">
                </div>

                <div>
                    <label for="currency" class="block text-xs font-semibold text-slate-700">Base Currency (ISO-3)</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency', $business->currency) }}"
                           placeholder="INR" maxlength="3"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] font-mono uppercase">
                </div>

                <div>
                    <label for="timezone" class="block text-xs font-semibold text-slate-700">Timezone</label>
                    <input type="text" name="timezone" id="timezone" value="{{ old('timezone', $business->timezone) }}"
                           placeholder="Asia/Kolkata"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] font-mono">
                </div>

                <div class="sm:col-span-2">
                    <label for="tax_number" class="block text-xs font-semibold text-slate-700">Tax / GST Number</label>
                    <input type="text" name="tax_number" id="tax_number" value="{{ old('tax_number', $business->tax_number) }}"
                           placeholder="e.g., 27AAAAA0000A1Z5"
                           class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#22B14C] font-mono">
                </div>
            </div>
        </div>

        <!-- Form Action Toolbar -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.businesses.show', $business) }}"
               class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" id="update-business-btn"
                    class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-[#22B14C] hover:bg-[#1E9E44] shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#22B14C] transition-colors">
                Save Changes
            </button>
        </div>
    </form>

    <script>
        document.getElementById('logo')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewImg = document.getElementById('logo-preview-img');
            const placeholderSvg = document.getElementById('logo-placeholder-svg');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    previewImg.src = evt.target.result;
                    previewImg.classList.remove('hidden');
                    if (placeholderSvg) placeholderSvg.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</div>
@endsection
