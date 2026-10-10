@extends('layouts.owner')

@inject('authService', 'App\Services\Auth\AuthorizationService')
@inject('moduleRegistry', 'App\Services\Tenancy\ModuleRegistry')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $canManageCatalogue = $tenant
        && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Catalogue)
        && $authService->can($user, 'catalogue.manage', $tenant);
@endphp
<div class="space-y-8">
    <!-- Page Header -->
    <x-page-header
        title="Commercial Dashboard"
        :description="'Operating overview for ' . $tenant->name . ' (' . $tenant->slug . '). Server-authoritative tenant isolation active.'"
        badge="Live Tenant"
        badgeVariant="emerald">
        <x-slot:breadcrumbs>
            <span class="text-slate-800 font-semibold">{{ $tenant->name }}</span>
            <span>/</span>
            <span>Dashboard</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                {{ $tenant->currency }} &bull; {{ $tenant->timezone }}
            </span>
        </x-slot:actions>
    </x-page-header>

    <!-- Tenant Context & Active Subscription Summary Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-950 rounded-2xl p-6 text-white shadow-xs border border-slate-800">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center space-x-3.5">
                    @if ($tenant?->logo_url)
                        <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/20 p-1 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->name }}" class="w-full h-full object-contain">
                        </div>
                    @endif
                    <div>
                        <div class="flex items-center space-x-3">
                            <h2 class="text-xl font-bold tracking-tight text-white">{{ $tenant->name }}</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-400/20 text-emerald-300 border border-emerald-400/30">
                                {{ $tenant->status->label() }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 max-w-2xl leading-relaxed mt-0.5">
                            {{ $tenant->legal_name ?? $tenant->name }} &bull; Type: <span class="capitalize font-semibold text-white">{{ $tenant->business_type }}</span> &bull;
                            Tax ID: <span class="font-mono text-white">{{ $tenant->tax_number ?? 'Not Configured' }}</span>
                        </p>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400">
                    Primary Owner: <span class="text-slate-200 font-medium">{{ auth()->user()->name }}</span> ({{ auth()->user()->email }})
                </p>
            </div>

            <div class="bg-white/10 backdrop-blur-xs rounded-xl p-4 border border-white/10 min-w-[240px] text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-slate-300 font-medium">Subscription Tier</span>
                    @if ($activeSubscription)
                        <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] bg-emerald-400/20 text-emerald-300">
                            {{ $metrics['subscription_status'] }}
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] bg-amber-400/20 text-amber-300">
                            No Plan
                        </span>
                    @endif
                </div>
                <p class="text-base font-bold text-white">{{ $metrics['subscription_plan'] }}</p>
                <div class="text-[11px] text-slate-300 flex justify-between pt-1 border-t border-white/10">
                    <span>Period End / Expiry:</span>
                    <span class="font-semibold text-white">{{ $metrics['subscription_ends_at'] ?? 'Indefinite' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Real Domain Metrics Grid -->
    <div>
        <x-section-header
            title="Tenant Operational Status"
            description="Real-time verified data from your business database."
            badge="Database Verified"
            badgeVariant="emerald" />

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Active Staff Count -->
            <x-stat-card
                label="Team Members"
                :value="$metrics['active_members']"
                subtext="Active tenant memberships"
                iconVariant="adoqa-green">
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <!-- Subscription Plan -->
            <x-stat-card
                label="Subscription Plan"
                :value="$metrics['subscription_plan']"
                :subtext="'Status: ' . $metrics['subscription_status']"
                iconVariant="adoqa-yellow">
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <!-- Business Status -->
            <x-stat-card
                label="Operating Status"
                :value="$metrics['business_status']"
                subtext="Isolated commercial tenant"
                iconVariant="emerald">
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <!-- Currency Profile -->
            <x-stat-card
                label="Financial Base"
                :value="$tenant->currency"
                :subtext="'Zone: ' . $tenant->timezone"
                iconVariant="sky">
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </div>
    </div>

    <!-- Future Commercial Operations (Truthful Empty States) -->
    <div>
        <x-section-header
            title="Today's Commercial Operations"
            description="Operational sales, cashier register, and financial flow."
            badge="Upcoming & Phase 4"
            badgeVariant="slate" />

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Today's Sales -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Today's Sales</p>
                    <x-badge variant="slate" size="sm">Upcoming</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['today_sales'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">POS and Invoicing modules are currently unavailable</p>
            </div>

            <!-- Operating Profit -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Operating Profit</p>
                    <x-badge variant="slate" size="sm">Phase 4</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['today_profit'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Financial ledger scheduled for Phase 4</p>
            </div>

            <!-- Business Expenses -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Business Expenses</p>
                    <x-badge variant="slate" size="sm">Phase 4</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['today_expenses'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Expense tracking scheduled for Phase 4</p>
            </div>

            <!-- Pending Receivables -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Pending Receivables</p>
                    <x-badge variant="slate" size="sm">Phase 4</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['pending_receivables'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Customer dues ledger scheduled for Phase 4</p>
            </div>
        </div>
    </div>

    <!-- Future Commerce & Inventory (Truthful Empty States) -->
    <div>
        <x-section-header
            title="Commerce & Customer Directory"
            description="Customers, inventory stock, and vendor payables."
            badge="Upcoming & Phase 4"
            badgeVariant="slate" />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Customers -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Registered Customers</p>
                    <x-badge variant="slate" size="sm">Upcoming</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['customer_count'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Customer directory module is currently unavailable</p>
            </div>

            <!-- Inventory Alerts -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Stock & Inventory Alerts</p>
                    <x-badge variant="slate" size="sm">Phase 4</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['inventory_alerts'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Inventory stock auditing scheduled for Phase 4</p>
            </div>

            <!-- Outstanding Payables -->
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-500">Supplier Payables</p>
                    <x-badge variant="slate" size="sm">Phase 4</x-badge>
                </div>
                <p class="text-sm font-semibold text-slate-500 mt-2 italic">{{ $metrics['outstanding_payables'] }}</p>
                <p class="text-[11px] text-slate-500 mt-1">Purchase orders & supplier accounts in Phase 4</p>
            </div>
        </div>
    </div>

    <!-- Analytics & Reporting Foundation -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Trend Placeholder -->
        <x-card title="Sales & Revenue Performance" subtitle="Real-time commercial transaction analytics">
            <x-empty-state
                title="Analytics awaiting commercial activity"
                description="Visual performance charts will populate here automatically once sales orders and invoices are generated through the upcoming POS module."
                badge="Upcoming" />
        </x-card>

        @if ($canManageCatalogue)
            <!-- Product Catalogue Available Card -->
            <x-card title="Product Catalogue" subtitle="Items, categories, brands, and measurement units" badge="Available" badgeVariant="emerald">
                <div class="space-y-3">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Catalogue foundation is active and server-authoritative. Manage master items, classification categories, manufacturer brands, and measurement units.
                    </p>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="{{ route('owner.catalogue.items.index') }}"
                           class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-[#E8F8EE] hover:border-[#22B14C]/30 transition-colors group">
                            <div class="flex items-center space-x-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-[#1C9640]">Items</span>
                            </div>
                            <span class="text-[10px] text-slate-600 group-hover:text-[#1C9640] font-medium">Manage &rarr;</span>
                        </a>

                        <a href="{{ route('owner.catalogue.categories.index') }}"
                           class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-[#E8F8EE] hover:border-[#22B14C]/30 transition-colors group">
                            <div class="flex items-center space-x-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-[#1C9640]">Categories</span>
                            </div>
                            <span class="text-[10px] text-slate-600 group-hover:text-[#1C9640] font-medium">Manage &rarr;</span>
                        </a>

                        <a href="{{ route('owner.catalogue.brands.index') }}"
                           class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-[#E8F8EE] hover:border-[#22B14C]/30 transition-colors group">
                            <div class="flex items-center space-x-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-[#1C9640]">Brands</span>
                            </div>
                            <span class="text-[10px] text-slate-600 group-hover:text-[#1C9640] font-medium">Manage &rarr;</span>
                        </a>

                        <a href="{{ route('owner.catalogue.units.index') }}"
                           class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-[#E8F8EE] hover:border-[#22B14C]/30 transition-colors group">
                            <div class="flex items-center space-x-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-semibold text-slate-800 group-hover:text-[#1C9640]">Units</span>
                            </div>
                            <span class="text-[10px] text-slate-600 group-hover:text-[#1C9640] font-medium">Manage &rarr;</span>
                        </a>
                    </div>

                    <p class="text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        Stock movements, velocity rankings, and POS checkout links will activate with Inventory and Sales modules.
                    </p>
                </div>
            </x-card>
        @endif
    </div>
</div>
@endsection
