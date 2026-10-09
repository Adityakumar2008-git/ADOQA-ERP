@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page_title', 'Roles & Permissions')

@section('content')
<div class="space-y-6">
    <!-- Header with Badges -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Roles & Permissions</h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-amber-500/20 text-amber-700 border border-amber-500/30">
                    Platform Scope
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-emerald-500/20 text-emerald-700 border border-emerald-500/30">
                    Deny-by-Default
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Super Admin role hierarchy and canonical role templates. Custom business role authoring will be managed here in future releases.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.tenant-controls.index') }}"
               class="inline-flex items-center px-3.5 py-1.5 rounded-lg shadow-2xs text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Tenant Feature & Role Controls
            </a>
        </div>
    </div>

    <!-- Tenant Feature & Role Controls Callout Banner -->
    <div class="p-5 rounded-xl bg-gradient-to-r from-indigo-900 to-slate-900 text-white shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4 border border-indigo-800">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                    Tenant Governance
                </span>
                <h2 class="text-base font-bold text-white">Customize permissions for a specific business tenant?</h2>
            </div>
            <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                Global canonical roles below are immutable templates. To enable or disable functional modules (Catalogue, Inventory, Procurement, etc.) or set ALLOW/DENY overrides for an individual business, use Tenant Feature &amp; Role Controls.
            </p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('admin.tenant-controls.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-xs">
                Open Tenant Feature &amp; Role Controls &rarr;
            </a>
        </div>
    </div>

    <!-- Platform Roles Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Platform-Scoped Roles</h2>
                <p class="text-xs text-slate-500 mt-0.5">Roles controlling platform operations, tenant provisioning, and system-wide administration.</p>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                System Immutable
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach ($platformRoles as $role)
                <div class="p-5 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                PR
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="text-sm font-bold text-slate-900">{{ $role->name }}</h3>
                                    <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-mono">{{ $role->slug }}</code>
                                    @if ($role->is_system)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">System</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $role->description ?? 'Platform administration role.' }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-slate-400 font-mono">
                            {{ $role->permissions->count() }} permissions assigned
                        </div>
                    </div>

                    <div class="pt-1 flex flex-wrap gap-1.5 items-center">
                        <span class="text-[11px] font-semibold uppercase text-slate-400 tracking-wider mr-1">Granted Capabilities:</span>
                        @foreach ($role->permissions as $perm)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-slate-50 text-slate-700 border border-slate-200" title="{{ $perm->name }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                {{ $perm->slug }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Tenant Canonical Template Roles Reference -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Canonical Tenant Template Roles (Reference)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Standard template roles provisioned for business tenants. Isolated within tenant boundaries.</p>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                Tenant Isolation Guaranteed
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                    <tr>
                        <th scope="col" class="px-5 py-3">Role Name</th>
                        <th scope="col" class="px-5 py-3">Slug</th>
                        <th scope="col" class="px-5 py-3">Description</th>
                        <th scope="col" class="px-5 py-3">Capabilities Count</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($systemBusinessRoles as $bRole)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3 font-bold text-slate-900">{{ $bRole->name }}</td>
                            <td class="px-5 py-3 font-mono text-slate-600">{{ $bRole->slug }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $bRole->description ?? 'Canonical tenant role template.' }}</td>
                            <td class="px-5 py-3 font-mono text-slate-700">{{ $bRole->permissions->count() }} permissions</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 text-[11px] text-slate-500">
            <strong>Architecture Notice:</strong> Master Admin maintains platform authority over the whole system, but does not participate in tenant business memberships. Individual business tenants instantiate their own isolated role and permission assignments.
        </div>
    </div>
</div>
@endsection
