@extends('layouts.admin')

@section('title', 'Tenant Feature & Role Controls')
@section('page_title', 'Tenant Feature & Role Controls')

@section('content')
<div class="space-y-6">
    <!-- Header with Breadcrumbs & Context Badges -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tenant Feature & Role Controls</h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-indigo-500/10 text-indigo-700 border border-indigo-500/20">
                    Tenant-Specific Governance
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                    Template Non-Destructive
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Customize functional module access and fine-tune role permissions per business tenant without altering global canonical templates.
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.roles.index') }}"
               class="inline-flex items-center px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-colors shadow-2xs">
                &larr; Global RBAC Registry
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-start justify-between shadow-2xs">
            <div class="flex items-center space-x-3">
                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">&check;</span>
                <p class="text-xs font-medium">{{ session('success') }}</p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-xs font-bold">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start justify-between shadow-2xs">
            <div class="flex items-center space-x-3">
                <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs shrink-0">&excl;</span>
                <p class="text-xs font-medium">{{ session('error') }}</p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 text-xs font-bold">&times;</button>
        </div>
    @endif

    <!-- STEP 1: Select Business Tenant -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5">
        <form method="GET" action="{{ route('admin.tenant-controls.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <label for="tenant_select" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    1. Select Target Business / Tenant
                </label>
                <div class="relative">
                    <select id="tenant_select" name="business_id" onchange="this.form.submit()"
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 bg-white text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Choose a Business Tenant --</option>
                        @foreach ($businesses as $b)
                            <option value="{{ $b->id }}" {{ $selectedBusiness && $selectedBusiness->id === $b->id ? 'selected' : '' }}>
                                {{ $b->name }} ({{ $b->slug }}) &bull; Status: {{ ucfirst($b->status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg transition-colors shadow-2xs">
                    Load Controls
                </button>
                @if ($selectedBusiness)
                    <a href="{{ route('admin.businesses.show', $selectedBusiness) }}"
                       class="px-3 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-xs rounded-lg transition-colors">
                        View Details
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if (! $selectedBusiness)
        <div class="p-12 text-center bg-white rounded-xl border border-dashed border-slate-300 shadow-2xs space-y-3">
            <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 mx-auto flex items-center justify-center font-bold text-lg">
                &rarr;
            </div>
            <h2 class="text-base font-bold text-slate-900">Select a Business Tenant to Begin</h2>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Choose a business tenant from the dropdown above to inspect and configure its active functional modules and customize permissions for its business roles.
            </p>
        </div>
    @else
        <!-- Tenant Summary Ribbon -->
        <div class="p-4 bg-slate-900 text-slate-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($selectedBusiness->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">{{ $selectedBusiness->name }}</h2>
                    <p class="text-[11px] text-slate-400 font-mono">ID: {{ $selectedBusiness->id }} &bull; Slug: {{ $selectedBusiness->slug }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    {{ ucfirst($selectedBusiness->status->value) }}
                </span>
                <span class="text-slate-400">
                    Currency: <strong class="text-white">{{ $selectedBusiness->currency }}</strong>
                </span>
            </div>
        </div>

        <!-- STEP 2: Tenant Module Controls -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px]">2</span>
                        <h2 class="text-sm font-bold text-slate-900">Tenant Module Controls</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Toggle supported ERP functional modules ON or OFF. Disabled modules immediately deny all protected operations on the server.
                    </p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 self-start sm:self-auto">
                    {{ count(array_filter($moduleStates, fn($m) => $m['is_enabled'])) }} / {{ count($moduleStates) }} Modules Active
                </span>
            </div>

            <form action="{{ route('admin.tenant-controls.modules.update') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">
                <input type="hidden" name="role_id" value="{{ $selectedRole?->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($moduleStates as $module)
                        <div class="p-4 rounded-xl border {{ $module['is_enabled'] ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/60' }} flex flex-col justify-between space-y-3 transition-colors">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-900">{{ $module['name'] }}</span>
                                    @if ($module['is_enabled'])
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            ACTIVE
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-600">
                                            DISABLED
                                        </span>
                                    @endif
                                </div>
                                <code class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 text-[10px] font-mono inline-block">
                                    {{ $module['slug'] }}
                                </code>
                                <p class="text-[11px] text-slate-500 leading-relaxed">{{ $module['description'] }}</p>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[11px] font-medium text-slate-600">Module Status</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="modules[{{ $module['slug'] }}]" value="0">
                                    <input type="checkbox" name="modules[{{ $module['slug'] }}]" value="1"
                                           {{ $module['is_enabled'] ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-xs transition-colors">
                        Save Module Configuration
                    </button>
                </div>
            </form>
        </div>

        <!-- STEP 3: Role Permission Overrides -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px]">3</span>
                        <h2 class="text-sm font-bold text-slate-900">Tenant Role-Permission Overrides</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Select a business-scoped role, view human-readable permissions grouped by module, and set overrides to INHERIT, ALLOW, or DENY.
                    </p>
                </div>

                <!-- Role Selector Tabs / Select -->
                <form method="GET" action="{{ route('admin.tenant-controls.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">
                    <label for="role_select" class="text-xs font-semibold text-slate-600 shrink-0">Role:</label>
                    <select id="role_select" name="role_id" onchange="this.form.submit()"
                            class="text-xs border border-slate-300 rounded-lg px-3 py-1.5 bg-white text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach ($businessRoles as $bRole)
                            <option value="{{ $bRole->id }}" {{ $selectedRole && $selectedRole->id === $bRole->id ? 'selected' : '' }}>
                                {{ $bRole->name }} ({{ $bRole->slug }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if (! $selectedRole)
                <div class="p-8 text-center text-xs text-slate-400 italic">
                    No business-scoped roles found for this tenant.
                </div>
            @else
                <form action="{{ route('admin.tenant-controls.overrides.update') }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="business_id" value="{{ $selectedBusiness->id }}">
                    <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">

                    <!-- Active Role Banner -->
                    <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr($selectedRole->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ $selectedRole->name }}</h3>
                                <p class="text-[11px] text-slate-500 font-mono">Slug: {{ $selectedRole->slug }} &bull; Scope: Business &bull; System: {{ $selectedRole->is_system ? 'Yes' : 'No' }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-slate-500">
                            Editing tenant overrides for <strong>{{ $selectedBusiness->name }}</strong>
                        </div>
                    </div>

                    <!-- Permissions Grouped by Module -->
                    <div class="space-y-6">
                        @foreach ($permissionsByModule as $group)
                            <div class="border border-slate-200 rounded-xl overflow-hidden {{ ! $group['is_enabled'] ? 'opacity-70 bg-slate-50/50' : 'bg-white' }}">
                                <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-slate-900">{{ $group['module_name'] }}</span>
                                        <code class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-slate-200 text-slate-700">{{ $group['module_slug'] }}</code>
                                    </div>
                                    <div>
                                        @if ($group['is_enabled'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                Module Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800" title="Protected operations denied because module is disabled">
                                                Module Disabled (Denied)
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="divide-y divide-slate-100">
                                    @foreach ($group['permissions'] as $item)
                                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 transition-colors">
                                            <div class="space-y-1 max-w-lg">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs font-bold text-slate-900">{{ $item['name'] }}</span>
                                                    <code class="text-[10px] font-mono text-slate-500">{{ $item['slug'] }}</code>
                                                </div>
                                                <div class="flex items-center space-x-2 text-[11px]">
                                                    <span class="text-slate-400">Template default:</span>
                                                    @if ($item['has_template'])
                                                        <span class="inline-flex items-center text-emerald-700 font-semibold text-[10px]">
                                                            &check; Granted by Template
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center text-slate-400 text-[10px]">
                                                            &times; Not in Template
                                                        </span>
                                                    @endif

                                                    <span class="text-slate-300">&bull;</span>
                                                    <span class="text-slate-400">Effective:</span>
                                                    @if ($item['effective'])
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                            PERMITTED
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                                            DENIED
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 3-Way Override Radio Selector -->
                                            <div class="flex items-center space-x-2 shrink-0">
                                                <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-100/60 text-xs font-medium">
                                                    <label class="relative cursor-pointer px-2.5 py-1 rounded-md transition-all flex items-center space-x-1.5 has-checked:bg-white has-checked:shadow-2xs has-checked:text-slate-900 text-slate-500">
                                                        <input type="radio" name="overrides[{{ $item['permission']->id }}]" value="inherit"
                                                               {{ $item['override_state'] === 'inherit' ? 'checked' : '' }}
                                                               onchange="this.form.submit()"
                                                               class="sr-only">
                                                        <span class="text-[11px]">INHERIT</span>
                                                    </label>

                                                    <label class="relative cursor-pointer px-2.5 py-1 rounded-md transition-all flex items-center space-x-1.5 has-checked:bg-emerald-600 has-checked:shadow-2xs has-checked:text-white text-emerald-700">
                                                        <input type="radio" name="overrides[{{ $item['permission']->id }}]" value="allow"
                                                               {{ $item['override_state'] === 'allow' ? 'checked' : '' }}
                                                               onchange="this.form.submit()"
                                                               class="sr-only">
                                                        <span class="text-[11px] font-bold">ALLOW</span>
                                                    </label>

                                                    <label class="relative cursor-pointer px-2.5 py-1 rounded-md transition-all flex items-center space-x-1.5 has-checked:bg-rose-600 has-checked:shadow-2xs has-checked:text-white text-rose-700">
                                                        <input type="radio" name="overrides[{{ $item['permission']->id }}]" value="deny"
                                                               {{ $item['override_state'] === 'deny' ? 'checked' : '' }}
                                                               onchange="this.form.submit()"
                                                               class="sr-only">
                                                        <span class="text-[11px] font-bold">DENY</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-lg shadow-xs transition-colors">
                            Save Role Overrides
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
