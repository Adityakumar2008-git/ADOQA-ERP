@inject('authService', 'App\Services\Auth\AuthorizationService')
@inject('tenantContext', 'App\Services\Tenancy\TenantContext')
@inject('moduleRegistry', 'App\Services\Tenancy\ModuleRegistry')

@php
    $tenant = request()->attributes->get('tenant') ?? $tenantContext->current();
    $user = auth()->user();

    // Module enablement checks
    $isCatalogueEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Catalogue);
    $isInventoryEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Inventory);
    $isProcurementEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Procurement);
    $isSalesEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Sales);
    $isCustomersEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Customers);
    $isFinanceEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Finance);
    $isPeopleEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::People);
    $isSettingsEnabled = $tenant && $moduleRegistry->isModuleEnabledForBusiness($tenant, \App\Enums\BusinessModule::Settings);

    // Catalogue permissions
    $canManageCatalogue = $isCatalogueEnabled && $authService->can($user, 'catalogue.manage', $tenant);

    // Inventory permissions (granular)
    $canViewStock = $isInventoryEnabled && $authService->can($user, 'inventory.view', $tenant);
    $canViewWarehouses = $isInventoryEnabled && ($authService->can($user, 'warehouses.view', $tenant) || $authService->can($user, 'warehouses.manage', $tenant));
    $canAdjustStock = $isInventoryEnabled && ($authService->can($user, 'inventory.adjust', $tenant) || $authService->can($user, 'inventory.manage', $tenant));
    $canTransferStock = $isInventoryEnabled && ($authService->can($user, 'inventory.transfer', $tenant) || $authService->can($user, 'inventory.manage', $tenant));
    $canReconcileStock = $isInventoryEnabled && ($authService->can($user, 'inventory.reconcile', $tenant) || $authService->can($user, 'inventory.manage', $tenant));
    $hasAnyInventoryNav = $isInventoryEnabled && ($canViewStock || $canViewWarehouses || $canAdjustStock || $canTransferStock || $canReconcileStock);

    // Procurement permissions (granular)
    $canViewSuppliers = $isProcurementEnabled && ($authService->can($user, 'suppliers.view', $tenant) || $authService->can($user, 'suppliers.manage', $tenant));
    $canOrders = $isProcurementEnabled && ($authService->can($user, 'purchases.manage', $tenant) || $authService->can($user, 'purchases.create', $tenant) || $authService->can($user, 'purchases.approve', $tenant));
    $canBills = $isProcurementEnabled && ($authService->can($user, 'purchases.bills.manage', $tenant) || $authService->can($user, 'purchases.bills.post', $tenant) || $authService->can($user, 'purchases.manage', $tenant));
    $canReceipts = $isProcurementEnabled && ($authService->can($user, 'purchases.receipts.prepare', $tenant) || $authService->can($user, 'purchases.receipts.post', $tenant) || $authService->can($user, 'purchases.manage', $tenant));
    $canPayments = $isProcurementEnabled && ($authService->can($user, 'suppliers.payments.create', $tenant) || $authService->can($user, 'suppliers.payments.post', $tenant) || $authService->can($user, 'purchases.manage', $tenant));
    $hasAnyProcurementNav = $isProcurementEnabled && ($canViewSuppliers || $canOrders || $canBills || $canReceipts || $canPayments);

    // Other role-aware sections
    $canSales = $isSalesEnabled && ($authService->can($user, 'sales.create', $tenant) || $authService->can($user, 'invoices.view', $tenant));
    $canFinance = $isFinanceEnabled && $authService->can($user, 'finance.manage', $tenant);
    $canPeople = $isPeopleEnabled && ($authService->can($user, 'payroll.manage', $tenant) || $authService->can($user, 'staff.manage', $tenant));
    $canSystem = $isSettingsEnabled && ($authService->can($user, 'settings.manage', $tenant) || $authService->can($user, 'audit.view', $tenant));

    $activeSub = $tenant?->activeSubscription;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Owner ERP') — {{ $tenant?->name ?? 'ADOQA ERP' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-100 flex">
    <!-- Left Persistent Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 border-r border-slate-800 select-none">
        <!-- Brand Header -->
        <div class="h-16 flex items-center px-5 border-b border-slate-800 justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm tracking-wider shadow-sm">
                    A
                </div>
                <div>
                    <span class="text-base font-bold text-white tracking-tight">ADOQA</span>
                    <span class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider block -mt-1">Owner ERP</span>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                Tenant
            </span>
        </div>

        <!-- Business Context Card -->
        <div class="p-3 mx-3 my-3 bg-slate-950/60 rounded-xl border border-slate-800/80 text-xs">
            <div class="flex items-center justify-between">
                <p class="font-bold text-white truncate max-w-[140px]" title="{{ $tenant?->name }}">{{ $tenant?->name ?? 'Business Account' }}</p>
                <span class="w-2 h-2 rounded-full bg-emerald-500" title="Active Business"></span>
            </div>
            <p class="text-[11px] text-slate-400 capitalize mt-0.5">{{ $tenant?->business_type ?? 'Commercial' }} &bull; {{ $tenant?->currency ?? 'INR' }}</p>

            <div class="mt-2 pt-2 border-t border-slate-800/60 flex items-center justify-between text-[10px]">
                <span class="text-slate-400 truncate max-w-[110px]">
                    {{ $activeSub?->plan?->name ?? 'No Plan' }}
                </span>
                @if ($activeSub)
                    <span class="px-1.5 py-0.2 rounded font-bold uppercase tracking-wider
                        @if ($activeSub->status->value === 'active') bg-emerald-500/20 text-emerald-300
                        @elseif ($activeSub->status->value === 'trial') bg-sky-500/20 text-sky-300
                        @else bg-amber-500/20 text-amber-300 @endif">
                        {{ ucfirst($activeSub->status->value) }}
                    </span>
                @else
                    <span class="text-slate-500 italic">Unassigned</span>
                @endif
            </div>
        </div>

        <!-- Role-Aware Navigation Menu -->
        <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-5 text-xs">
            <!-- PRIMARY Section -->
            <div>
                <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Primary</p>
                <div class="space-y-1">
                    <a href="{{ route('owner.dashboard') }}"
                       class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.dashboard*') || request()->routeIs('owner.index') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.dashboard*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>

            <!-- SALES Section (Role-Aware) -->
            @if ($canSales)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sales</p>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 bg-slate-800 px-1 py-0.2 rounded">Phase 3</span>
                    </div>
                    <div class="space-y-0.5 opacity-60 text-[11px] select-none">
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>New Bill (POS)</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>All Invoices</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Sale Returns</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Quotations</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Bookings</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- COMMERCE Section (Role-Aware) -->
            @if ($canManageCatalogue)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Commerce</p>
                    </div>

                    <!-- Active Phase 3 Catalogue Modules -->
                    <div class="space-y-1">
                        <a href="{{ route('owner.catalogue.items.index') }}"
                           class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.catalogue.items.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.catalogue.items.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Items Catalogue</span>
                        </a>

                        <a href="{{ route('owner.catalogue.categories.index') }}"
                           class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.catalogue.categories.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.catalogue.categories.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span>Categories</span>
                        </a>

                        <a href="{{ route('owner.catalogue.brands.index') }}"
                           class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.catalogue.brands.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.catalogue.brands.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Brands</span>
                        </a>

                        <a href="{{ route('owner.catalogue.units.index') }}"
                           class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.catalogue.units.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.catalogue.units.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 18h12l3-18H3z"/>
                            </svg>
                            <span>Units</span>
                        </a>
                    </div>

                    <!-- Pending Commerce Modules -->
                    @if ($isCustomersEnabled)
                        <div class="mt-2 pt-2 border-t border-slate-800/60 space-y-0.5 opacity-50 text-[11px] select-none">
                            <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                                <span>Customers</span>
                                <span class="text-[9px] text-slate-500">Coming soon</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- INVENTORY Section (Phase 4 Active) -->
            @if ($hasAnyInventoryNav)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Inventory</p>
                    </div>
                    <div class="space-y-1">
                        @if ($canViewStock)
                            <a href="{{ route('owner.inventory.stock.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.inventory.stock.index') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.inventory.stock.index') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span>Stock Balances</span>
                            </a>

                            <a href="{{ route('owner.inventory.stock.ledger') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.inventory.stock.ledger') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.inventory.stock.ledger') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Stock Ledger</span>
                            </a>
                        @endif

                        @if ($canViewWarehouses)
                            <a href="{{ route('owner.inventory.warehouses.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.inventory.warehouses.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.inventory.warehouses.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>Warehouses</span>
                            </a>
                        @endif

                        @if ($canAdjustStock)
                            <a href="{{ route('owner.inventory.adjustments.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.inventory.adjustments.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.inventory.adjustments.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Adjustments</span>
                            </a>
                        @endif

                        @if ($canTransferStock)
                            <a href="{{ route('owner.inventory.transfers.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.inventory.transfers.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.inventory.transfers.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span>Internal Transfers</span>
                            </a>
                        @endif

                        @if ($canReconcileStock)
                            <a href="{{ route('owner.inventory.stock.reconcile') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.inventory.stock.reconcile') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.inventory.stock.reconcile') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Reconciliation</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- PURCHASING Section (Phase 4 Active) -->
            @if ($hasAnyProcurementNav)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Procurement</p>
                    </div>
                    <div class="space-y-1">
                        @if ($canViewSuppliers)
                            <a href="{{ route('owner.purchasing.suppliers.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.purchasing.suppliers.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.purchasing.suppliers.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>Suppliers</span>
                            </a>
                        @endif

                        @if ($canOrders)
                            <a href="{{ route('owner.purchasing.orders.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.purchasing.orders.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.purchasing.orders.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Purchase Orders</span>
                            </a>
                        @endif

                        @if ($canBills)
                            <a href="{{ route('owner.purchasing.bills.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.purchasing.bills.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.purchasing.bills.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                                <span>Purchase Bills</span>
                            </a>
                        @endif

                        @if ($canReceipts)
                            <a href="{{ route('owner.purchasing.receipts.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.purchasing.receipts.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.purchasing.receipts.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <span>Goods Receipts</span>
                            </a>
                        @endif

                        @if ($canPayments)
                            <a href="{{ route('owner.purchasing.payments.index') }}"
                               class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('owner.purchasing.payments.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-4 h-4 mr-3 {{ request()->routeIs('owner.purchasing.payments.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>Supplier Payments</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <!-- FINANCE Section (Role-Aware: Hidden from non-finance roles) -->
            @if ($canFinance)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Finance</p>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 bg-slate-800 px-1 py-0.2 rounded">Phase 4</span>
                    </div>
                    <div class="space-y-0.5 opacity-60 text-[11px] select-none">
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Finance Ledger</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Expenses</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Receivables</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Payables</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>EMI Accounts</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- PEOPLE Section (Role-Aware: Hidden from unauthorized roles) -->
            @if ($canPeople)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">People &amp; HR</p>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 bg-slate-800 px-1 py-0.2 rounded">Phase 4</span>
                    </div>
                    <div class="space-y-0.5 opacity-60 text-[11px] select-none">
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Staff Directory</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Attendance</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Payroll Runs</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Leave Requests</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Salary Advances</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Staff Performance</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- SYSTEM Section (Role-Aware) -->
            @if ($canSystem)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">System</p>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 bg-slate-800 px-1 py-0.2 rounded">Phase 4</span>
                    </div>
                    <div class="space-y-0.5 opacity-60 text-[11px] select-none">
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Reports</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>System Logs</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Business Settings</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                    </div>
                </div>
            @endif
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <div class="truncate mr-2">
                    <p class="text-xs font-semibold text-white truncate">{{ $user->name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ $user->email }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout"
                            class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Top Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sm:px-8 sticky top-0 z-10 shadow-2xs">
            <div class="flex items-center space-x-3 text-sm">
                <span class="font-bold text-slate-800 tracking-tight">@yield('page_title', 'Business Dashboard')</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-500 font-medium truncate max-w-[200px] sm:max-w-xs">{{ $tenant?->name }}</span>
            </div>

            <!-- Global Search Placeholder & Top Actions -->
            <div class="flex items-center space-x-4">
                <div class="hidden md:flex items-center relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" disabled
                           placeholder="Search invoices, items... (Ctrl+K)"
                           class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs placeholder-slate-400 cursor-not-allowed w-64 select-none">
                </div>

                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                    <span class="font-bold mr-1">{{ $tenant?->currency ?? 'INR' }}</span> ({{ $tenant?->timezone ?? 'Asia/Kolkata' }})
                </span>

                <div class="h-4 w-px bg-slate-200"></div>

                <!-- Notifications Foundation -->
                <button type="button" disabled title="Notifications (Coming in Phase 3)"
                        class="p-1.5 text-slate-400 rounded-lg cursor-not-allowed hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                <!-- User Account Profile -->
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center border border-indigo-200 select-none">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-600 transition-colors">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Flash Alert Messages -->
        <div class="px-6 sm:px-8 pt-6">
            @if (session('success'))
                <x-alert type="success" title="Success">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session('error'))
                <x-alert type="error" title="Error">
                    {{ session('error') }}
                </x-alert>
            @endif

            @if ($errors->any())
                <x-alert type="error" title="Validation Error">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif
        </div>

        <!-- Main Workspace Area -->
        <main class="flex-1 px-6 sm:px-8 pb-12 pt-2">
            @yield('content')
        </main>
    </div>

    <!-- Back/Forward Cache (bfcache) Security Safeguard -->
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
