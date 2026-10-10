<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100 overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Master Admin') — {{ config('app.name', 'ADOQA ERP') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/adoqa-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans text-slate-800 bg-slate-100 flex overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 border-r border-slate-800 select-none">
        <!-- Brand Header -->
        <div class="h-16 flex items-center px-6 border-b border-slate-800 justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-lg bg-white p-1 flex items-center justify-center shadow-sm flex-shrink-0">
                    <img src="{{ asset('images/adoqa-logo.png') }}" alt="ADOQA Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="text-base font-bold text-white tracking-tight">ADOQA</span>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block -mt-1">Master Admin</span>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                Platform
            </span>
        </div>

        <!-- Navigation Scrollable Area -->
        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6 text-xs">
            <!-- GLOBAL Section -->
            <div>
                <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Global</p>
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.overview') ? 'bg-[#22B14C]/15 text-[#B5E61D] border border-[#22B14C]/35' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.overview') ? 'text-[#FFDE2D]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        System Overview
                    </a>

                    <a href="{{ route('admin.businesses.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.businesses.*') ? 'bg-[#22B14C]/15 text-[#B5E61D] border border-[#22B14C]/35' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.businesses.*') ? 'text-[#FFDE2D]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Businesses / Tenants
                    </a>

                    <a href="{{ route('admin.subscription-plans.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.subscription-plans.*') ? 'bg-[#22B14C]/15 text-[#B5E61D] border border-[#22B14C]/35' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.subscription-plans.*') ? 'text-[#FFDE2D]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Subscription Plans
                    </a>
                </div>
            </div>

            <!-- PARTNER PROGRAM Section (Progressive / Future) -->
            <div>
                <div class="flex items-center justify-between px-2 mb-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Partner Program</p>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-amber-400/90 bg-amber-400/10 px-1.5 py-0.5 rounded border border-amber-400/20">Phase 2+</span>
                </div>
                <div class="space-y-1 opacity-50 select-none">
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Sales Agents</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Leads</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Message Templates</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Campaign Logs</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Marketing Assets</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Commissions</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 rounded text-slate-400 cursor-not-allowed">
                        <span>Payout Requests</span>
                        <span class="text-[9px] text-slate-400">Locked</span>
                    </div>
                </div>
            </div>

            <!-- ADMINISTRATION Section -->
            <div>
                <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Administration</p>
                <div class="space-y-1">
                    <a href="{{ route('admin.roles.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.roles.*') ? 'bg-[#22B14C]/15 text-[#B5E61D] border border-[#22B14C]/35' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.roles.*') ? 'text-[#FFDE2D]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Roles & Permissions
                    </a>

                    <a href="{{ route('admin.tenant-controls.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg font-medium transition-colors {{ request()->routeIs('admin.tenant-controls.*') ? 'bg-[#22B14C]/15 text-[#B5E61D] border border-[#22B14C]/35' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 mr-3 {{ request()->routeIs('admin.tenant-controls.*') ? 'text-[#FFDE2D]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        Tenant Feature & Role Controls
                    </a>

                    <div class="flex items-center justify-between px-3 py-1.5 text-slate-400 opacity-50 cursor-not-allowed">
                        <span>All Users</span>
                        <span class="text-[9px] text-slate-400">Phase 2+</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 text-slate-400 opacity-50 cursor-not-allowed">
                        <span>Platform Settings</span>
                        <span class="text-[9px] text-slate-400">Phase 2+</span>
                    </div>
                    <div class="flex items-center justify-between px-3 py-1.5 text-slate-400 opacity-50 cursor-not-allowed">
                        <span>System Logs</span>
                        <span class="text-[9px] text-slate-400">Phase 2+</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <div class="truncate mr-2">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
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
    <div id="main-scroll-viewport" class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <!-- Top Bar -->
        <header class="h-16 adoqa-glass-header bg-white/95 flex items-center justify-between px-8 sticky top-0 z-10 shadow-2xs">
            <div class="flex items-center space-x-3 text-sm">
                <span class="font-semibold text-slate-800 tracking-tight">@yield('page_title', 'System Overview')</span>
                <span class="text-slate-300">/</span>
                <span class="text-xs text-slate-500 font-medium">Platform Administration</span>
            </div>

            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Master Scope Active
                </span>
                <div class="h-4 w-px bg-slate-200"></div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-600 transition-colors">
                        Sign Out
                    </button>
                </form>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-8 pt-6">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 mb-4 flex items-center justify-between" role="alert">
                    <div class="flex items-center text-sm font-medium text-emerald-800">
                        <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 mb-4 flex items-center justify-between" role="alert">
                    <div class="flex items-center text-sm font-medium text-rose-800">
                        <svg class="w-5 h-5 mr-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 mb-4" role="alert">
                    <div class="flex items-start text-sm font-medium text-rose-800">
                        <svg class="w-5 h-5 mr-3 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-semibold mb-1">Please correct the following errors:</p>
                            <ul class="list-disc list-inside space-y-1 text-xs">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="flex-1 px-8 pb-12 pt-2">
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

    <!-- Viewport Scroll Restoration Safeguard -->
    <script>
        (function() {
            const viewport = document.getElementById('main-scroll-viewport');
            if (!viewport) return;
            const storageKey = 'adoqa_admin_scroll_' + window.location.pathname + window.location.search;
            const saved = sessionStorage.getItem(storageKey);
            if (saved !== null) {
                viewport.scrollTop = parseInt(saved, 10);
                sessionStorage.removeItem(storageKey);
            }
            let scrollTimer;
            viewport.addEventListener('scroll', function() {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function() {
                    sessionStorage.setItem(storageKey, viewport.scrollTop);
                }, 100);
            }, { passive: true });
            window.addEventListener('beforeunload', function() {
                sessionStorage.setItem(storageKey, viewport.scrollTop);
            });
        })();
    </script>
</body>
</html>
