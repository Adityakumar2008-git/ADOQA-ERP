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
                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-[#E8F8EE]0/10 text-[#0E5C25] border border-[#22B14C]/30">
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
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 bg-white text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#22B14C] focus:border-[#22B14C]">
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
            <div class="w-12 h-12 rounded-full bg-[#E8F8EE] text-[#1C9640] mx-auto flex items-center justify-center font-bold text-lg">
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
                <div class="w-9 h-9 rounded-lg bg-[#22B14C] text-white flex items-center justify-center font-bold text-sm">
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
                        <span class="w-5 h-5 rounded-full bg-[#E8F8EE] text-[#0E5C25] flex items-center justify-center font-bold text-[10px]">2</span>
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
                    <button type="submit" class="px-4 py-2 bg-[#22B14C] hover:bg-[#1E9E44] text-white font-semibold text-xs rounded-lg shadow-xs transition-colors">
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
                        <span class="w-5 h-5 rounded-full bg-[#E8F8EE] text-[#0E5C25] flex items-center justify-center font-bold text-[10px]">3</span>
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
                            class="text-xs border border-slate-300 rounded-lg px-3 py-1.5 bg-white text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-[#22B14C]">
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
                            <div class="w-8 h-8 rounded-lg bg-[#22B14C]/10 text-[#0E5C25] flex items-center justify-center font-bold text-xs">
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
                                        @php
                                            $isOverride = $item['override_state'] !== 'inherit';
                                            $switchOn = $item['override_state'] === 'allow' || ($item['override_state'] === 'inherit' && $item['has_template']);
                                        @endphp
                                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 transition-colors"
                                             id="row-perm-{{ $item['permission']->id }}"
                                             data-perm-id="{{ $item['permission']->id }}"
                                             data-has-template="{{ $item['has_template'] ? '1' : '0' }}"
                                             data-module-enabled="{{ $group['is_enabled'] ? '1' : '0' }}"
                                             data-override-state="{{ $item['override_state'] }}">
                                            <div class="space-y-1.5 max-w-lg">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs font-bold text-slate-900">{{ $item['name'] }}</span>
                                                    <code class="text-[10px] font-mono text-slate-500">{{ $item['slug'] }}</code>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2 text-[11px]">
                                                    <span class="text-slate-400">Template:</span>
                                                    @if ($item['has_template'])
                                                        <span class="inline-flex items-center text-emerald-700 font-semibold text-[10px]">
                                                            &check; Granted
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center text-slate-400 text-[10px]">
                                                            &times; Not in Template
                                                        </span>
                                                    @endif

                                                    <span class="text-slate-300">&bull;</span>

                                                    <!-- Override State Badge -->
                                                    <span id="override-badge-{{ $item['permission']->id }}"
                                                          class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $item['override_state'] === 'allow' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($item['override_state'] === 'deny' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                                        @if ($item['override_state'] === 'allow')
                                                            Explicit: ALLOW
                                                        @elseif ($item['override_state'] === 'deny')
                                                            Explicit: DENY
                                                        @else
                                                            Inherited
                                                        @endif
                                                    </span>

                                                    <span class="text-slate-300">&bull;</span>

                                                    <!-- Effective Evaluation Badge -->
                                                    <span id="effective-badge-{{ $item['permission']->id }}"
                                                          class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ ! $group['is_enabled'] ? 'bg-amber-100 text-amber-800 border border-amber-300' : ($item['effective'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}"
                                                          title="{{ ! $group['is_enabled'] ? 'Module disabled: protected operations blocked regardless of override' : ($item['effective'] ? 'Effective permission is PERMITTED' : 'Effective permission is DENIED') }}">
                                                        @if (! $group['is_enabled'])
                                                            BLOCKED BY MODULE
                                                        @elseif ($item['effective'])
                                                            PERMITTED
                                                        @else
                                                            DENIED
                                                        @endif
                                                    </span>
                                                </div>
                                                @if (! $group['is_enabled'])
                                                    <p class="text-[10px] text-amber-600 font-medium">
                                                        Parent module is disabled for this business tenant. Operations are blocked at runtime.
                                                    </p>
                                                @endif
                                            </div>

                                            <!-- Toggle Controls -->
                                            <div class="flex items-center space-x-3 shrink-0">
                                                <!-- Toast / Save Feedback -->
                                                <span id="save-feedback-{{ $item['permission']->id }}"
                                                      class="text-[11px] font-medium text-emerald-600 transition-opacity duration-300 opacity-0 flex items-center space-x-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <span>Saved</span>
                                                </span>

                                                <!-- Reset to Inherited Button -->
                                                <button type="button"
                                                        id="reset-btn-{{ $item['permission']->id }}"
                                                        onclick="resetToInherited('{{ $item['permission']->id }}')"
                                                        class="text-[11px] text-slate-500 hover:text-[#1C9640] underline font-medium transition-colors {{ $isOverride ? '' : 'hidden' }}"
                                                        title="Restore inherited role template permission">
                                                    Reset to inherited
                                                </button>

                                                <!-- Toggle Switch Button -->
                                                <div class="flex items-center space-x-2">
                                                    <button type="button"
                                                            role="switch"
                                                            aria-checked="{{ $switchOn ? 'true' : 'false' }}"
                                                            id="toggle-btn-{{ $item['permission']->id }}"
                                                            onclick="togglePermission('{{ $item['permission']->id }}')"
                                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#22B14C] focus:ring-offset-2 {{ $switchOn ? ($item['override_state'] === 'inherit' ? 'bg-emerald-400' : 'bg-emerald-600') : ($item['override_state'] === 'inherit' ? 'bg-slate-300' : 'bg-rose-500') }}"
                                                            title="{{ $item['override_state'] === 'inherit' ? 'Inherited default (' . ($switchOn ? 'Granted' : 'Not Granted') . ') - Click to set override' : ($switchOn ? 'Explicit ALLOW - Click to set explicit DENY' : 'Explicit DENY - Click to set explicit ALLOW') }}">
                                                        <span class="sr-only">Toggle {{ $item['name'] }}</span>
                                                        <span aria-hidden="true"
                                                              id="toggle-thumb-{{ $item['permission']->id }}"
                                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $switchOn ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                    </button>
                                                    <span id="toggle-label-{{ $item['permission']->id }}"
                                                          class="text-[11px] font-bold w-12 select-none {{ $switchOn ? 'text-emerald-700' : 'text-slate-600' }}">
                                                        {{ $item['override_state'] === 'inherit' ? ($switchOn ? 'ON*' : 'OFF*') : ($switchOn ? 'ALLOW' : 'DENY') }}
                                                    </span>
                                                </div>

                                                <!-- Hidden input for standard POST fallback -->
                                                <input type="hidden"
                                                       name="overrides[{{ $item['permission']->id }}]"
                                                       id="hidden-input-{{ $item['permission']->id }}"
                                                       value="{{ $item['override_state'] }}">
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

                <script>
                    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const BATCH_OVERRIDE_URL = "{{ route('admin.tenant-controls.overrides.update') }}";
                    const BUSINESS_ID = "{{ $selectedBusiness->id }}";
                    const ROLE_ID = "{{ $selectedRole->id }}";

                    async function sendOverrideUpdate(permissionId, newState) {
                        const toggleBtn = document.getElementById('toggle-btn-' + permissionId);
                        const resetBtn = document.getElementById('reset-btn-' + permissionId);
                        const feedback = document.getElementById('save-feedback-' + permissionId);

                        if (toggleBtn) toggleBtn.disabled = true;
                        if (resetBtn) resetBtn.disabled = true;

                        try {
                            const response = await fetch(BATCH_OVERRIDE_URL, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': CSRF_TOKEN,
                                },
                                body: JSON.stringify({
                                    business_id: BUSINESS_ID,
                                    role_id: ROLE_ID,
                                    permission_id: permissionId,
                                    state: newState,
                                }),
                            });

                            if (!response.ok) {
                                const errData = await response.json();
                                alert(errData.error || 'Failed to update permission override.');
                                return;
                            }

                            const data = await response.json();
                            applyStateToRow(permissionId, data.override_state, data.has_template, data.effective, data.module_enabled);

                            if (feedback) {
                                feedback.classList.remove('opacity-0');
                                setTimeout(() => {
                                    feedback.classList.add('opacity-0');
                                }, 1500);
                            }
                        } catch (err) {
                            console.error('Network error updating override:', err);
                            alert('A network error occurred. Please refresh the page.');
                        } finally {
                            if (toggleBtn) toggleBtn.disabled = false;
                            if (resetBtn) resetBtn.disabled = false;
                        }
                    }

                    function applyStateToRow(permissionId, overrideState, hasTemplate, effective, moduleEnabled) {
                        const row = document.getElementById('row-perm-' + permissionId);
                        const toggleBtn = document.getElementById('toggle-btn-' + permissionId);
                        const toggleThumb = document.getElementById('toggle-thumb-' + permissionId);
                        const toggleLabel = document.getElementById('toggle-label-' + permissionId);
                        const overrideBadge = document.getElementById('override-badge-' + permissionId);
                        const effectiveBadge = document.getElementById('effective-badge-' + permissionId);
                        const resetBtn = document.getElementById('reset-btn-' + permissionId);
                        const hiddenInput = document.getElementById('hidden-input-' + permissionId);

                        if (row) {
                            row.setAttribute('data-override-state', overrideState);
                        }
                        if (hiddenInput) {
                            hiddenInput.value = overrideState;
                        }

                        const switchOn = overrideState === 'allow' || (overrideState === 'inherit' && hasTemplate);

                        if (toggleBtn && toggleThumb && toggleLabel) {
                            toggleBtn.setAttribute('aria-checked', switchOn ? 'true' : 'false');
                            toggleBtn.className = 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#22B14C] focus:ring-offset-2 ' +
                                (switchOn
                                    ? (overrideState === 'inherit' ? 'bg-emerald-400' : 'bg-emerald-600')
                                    : (overrideState === 'inherit' ? 'bg-slate-300' : 'bg-rose-500'));

                            toggleThumb.className = 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ' +
                                (switchOn ? 'translate-x-5' : 'translate-x-0');

                            toggleLabel.className = 'text-[11px] font-bold w-12 select-none ' + (switchOn ? 'text-emerald-700' : 'text-slate-600');
                            toggleLabel.textContent = overrideState === 'inherit'
                                ? (switchOn ? 'ON*' : 'OFF*')
                                : (switchOn ? 'ALLOW' : 'DENY');
                        }

                        if (overrideBadge) {
                            if (overrideState === 'allow') {
                                overrideBadge.className = 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200';
                                overrideBadge.textContent = 'Explicit: ALLOW';
                            } else if (overrideState === 'deny') {
                                overrideBadge.className = 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border bg-rose-50 text-rose-700 border-rose-200';
                                overrideBadge.textContent = 'Explicit: DENY';
                            } else {
                                overrideBadge.className = 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border bg-slate-100 text-slate-600 border-slate-200';
                                overrideBadge.textContent = 'Inherited';
                            }
                        }

                        if (effectiveBadge) {
                            if (!moduleEnabled) {
                                effectiveBadge.className = 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300';
                                effectiveBadge.textContent = 'BLOCKED BY MODULE';
                            } else if (effective) {
                                effectiveBadge.className = 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800';
                                effectiveBadge.textContent = 'PERMITTED';
                            } else {
                                effectiveBadge.className = 'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600';
                                effectiveBadge.textContent = 'DENIED';
                            }
                        }

                        if (resetBtn) {
                            if (overrideState !== 'inherit') {
                                resetBtn.classList.remove('hidden');
                            } else {
                                resetBtn.classList.add('hidden');
                            }
                        }
                    }

                    function togglePermission(permissionId) {
                        const row = document.getElementById('row-perm-' + permissionId);
                        if (!row) return;

                        const currentState = row.getAttribute('data-override-state');
                        const hasTemplate = row.getAttribute('data-has-template') === '1';

                        let targetState;
                        if (currentState === 'allow') {
                            targetState = 'deny';
                        } else if (currentState === 'deny') {
                            targetState = 'allow';
                        } else {
                            targetState = hasTemplate ? 'deny' : 'allow';
                        }

                        sendOverrideUpdate(permissionId, targetState);
                    }

                    function resetToInherited(permissionId) {
                        sendOverrideUpdate(permissionId, 'inherit');
                    }
                </script>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
