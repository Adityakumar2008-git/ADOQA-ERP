@inject('authService', 'App\Services\Auth\AuthorizationService')
@inject('tenantContext', 'App\Services\Tenancy\TenantContext')

@php
    $tenant = request()->attributes->get('tenant') ?? $tenantContext->current();
    $user = auth()->user();

    // Role-aware permission resolution
    $canSales = $tenant && ($authService->can($user, 'sales.create', $tenant) || $authService->can($user, 'invoices.view', $tenant));
    $canCommerce = $tenant && ($authService->can($user, 'catalogue.manage', $tenant) || $authService->can($user, 'inventory.view', $tenant) || $authService->can($user, 'customers.manage', $tenant));
    $canFinance = $tenant && $authService->can($user, 'finance.manage', $tenant);
    $canPeople = $tenant && ($authService->can($user, 'payroll.manage', $tenant) || $authService->can($user, 'staff.manage', $tenant));
    $canSystem = $tenant && ($authService->can($user, 'settings.manage', $tenant) || $authService->can($user, 'audit.view', $tenant));

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
            @if ($canCommerce)
                <div>
                    <div class="flex items-center justify-between px-2 mb-1.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Commerce</p>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500 bg-slate-800 px-1 py-0.2 rounded">Phase 3</span>
                    </div>
                    <div class="space-y-0.5 opacity-60 text-[11px] select-none">
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Customers</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Suppliers</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Items Catalogue</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Categories &amp; Brands</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Inventory Stock</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                            <span>Purchases</span>
                            <span class="text-[9px] text-slate-500">Coming soon</span>
                        </div>
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
</body>
</html>
