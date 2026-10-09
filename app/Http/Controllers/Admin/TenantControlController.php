<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenancy\SyncBusinessRolePermissionOverrides;
use App\Actions\Tenancy\UpdateBusinessModuleSettings;
use App\Enums\BusinessModule;
use App\Enums\RoleScope;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessRolePermissionOverride;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Tenancy\ModuleRegistry;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantControlController extends Controller
{
    /**
     * Master Admin dedicated surface for Tenant Feature & Role Controls.
     * Allows selecting a tenant, selecting one of its business-scoped roles,
     * viewing permissions grouped by module, configuring INHERIT/ALLOW/DENY overrides,
     * and toggling supported tenant modules ON/OFF.
     */
    public function index(Request $request, ModuleRegistry $moduleRegistry): View
    {
        $businesses = Business::query()
            ->orderBy('name')
            ->get();

        $selectedBusiness = null;
        $moduleStates = [];
        $businessRoles = collect();
        $selectedRole = null;
        $permissionsByModule = [];
        $existingOverrides = collect();

        $businessId = $request->query('business_id');
        if ($businessId && ($business = $businesses->firstWhere('id', $businessId))) {
            $selectedBusiness = $business;

            // 1. Module settings for selected business
            foreach ($moduleRegistry->allModules() as $module) {
                $moduleStates[] = [
                    'enum' => $module,
                    'slug' => $module->value,
                    'name' => $module->label(),
                    'description' => $module->description(),
                    'is_enabled' => $moduleRegistry->isModuleEnabledForBusiness($selectedBusiness, $module),
                ];
            }

            // 2. Roles available to this business
            $businessRoles = Role::where('scope', RoleScope::Business)
                ->where(function ($q) use ($selectedBusiness) {
                    $q->whereNull('business_id')
                        ->orWhere('business_id', $selectedBusiness->id);
                })
                ->orderBy('is_system', 'desc')
                ->orderBy('name')
                ->get();

            // 3. Selected role
            $roleId = $request->query('role_id');
            if ($roleId) {
                $selectedRole = $businessRoles->firstWhere('id', $roleId);
            }

            // Default to first role if not specified
            if (! $selectedRole && $businessRoles->isNotEmpty()) {
                $selectedRole = $businessRoles->first();
            }

            // 4. If a role is selected, prepare grouped permissions and override states
            if ($selectedRole) {
                $templatePermissionSlugs = $selectedRole->permissions()
                    ->pluck('slug')
                    ->flip()
                    ->all();

                $overrides = BusinessRolePermissionOverride::query()
                    ->where('business_id', $selectedBusiness->id)
                    ->where('role_id', $selectedRole->id)
                    ->get()
                    ->keyBy('permission_id');

                $allBusinessPermissions = Permission::where('scope', RoleScope::Business)->get()->keyBy('slug');

                foreach ($moduleRegistry->allModules() as $module) {
                    $modulePermSlugs = $moduleRegistry->getPermissionsForModule($module);
                    $moduleEnabled = $moduleRegistry->isModuleEnabledForBusiness($selectedBusiness, $module);
                    $permItems = [];

                    foreach ($modulePermSlugs as $slug) {
                        $permModel = $allBusinessPermissions->get($slug);
                        if (! $permModel) {
                            continue;
                        }

                        $hasTemplate = isset($templatePermissionSlugs[$slug]);
                        $override = $overrides->get($permModel->id);
                        $overrideState = $override ? $override->state->value : 'inherit';

                        // Calculate effective evaluation
                        $effective = false;
                        if ($moduleEnabled) {
                            if ($overrideState === 'allow') {
                                $effective = true;
                            } elseif ($overrideState === 'deny') {
                                $effective = false;
                            } else {
                                $effective = $hasTemplate;
                            }
                        }

                        $permItems[] = [
                            'permission' => $permModel,
                            'slug' => $slug,
                            'name' => $permModel->name,
                            'has_template' => $hasTemplate,
                            'override_state' => $overrideState,
                            'effective' => $effective,
                            'module_enabled' => $moduleEnabled,
                        ];
                    }

                    if (! empty($permItems)) {
                        $permissionsByModule[] = [
                            'module' => $module,
                            'module_slug' => $module->value,
                            'module_name' => $module->label(),
                            'module_description' => $module->description(),
                            'is_enabled' => $moduleEnabled,
                            'permissions' => $permItems,
                        ];
                    }
                }
            }
        }

        return view('admin.tenant-controls.index', [
            'businesses' => $businesses,
            'selectedBusiness' => $selectedBusiness,
            'moduleStates' => $moduleStates,
            'businessRoles' => $businessRoles,
            'selectedRole' => $selectedRole,
            'permissionsByModule' => $permissionsByModule,
        ]);
    }

    /**
     * Atomically update module enabled/disabled states for a tenant.
     */
    public function updateModules(
        Request $request,
        UpdateBusinessModuleSettings $action
    ): RedirectResponse {
        $validated = $request->validate([
            'business_id' => ['required', 'string', 'exists:businesses,id'],
            'modules' => ['nullable', 'array'],
        ]);

        $business = Business::findOrFail($validated['business_id']);
        $rawModules = $validated['modules'] ?? [];

        $modules = [];
        foreach (BusinessModule::cases() as $module) {
            $modules[$module->value] = isset($rawModules[$module->value]) && (bool) $rawModules[$module->value];
        }

        try {
            $action->execute(
                actor: $request->user(),
                business: $business,
                modules: $modules
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.tenant-controls.index', [
            'business_id' => $business->id,
            'role_id' => $request->input('role_id'),
        ])->with('success', "Module configurations for '{$business->name}' updated successfully.");
    }

    /**
     * Atomically batch-update permission overrides for a role in a tenant.
     */
    public function updateRoleOverrides(
        Request $request,
        SyncBusinessRolePermissionOverrides $action
    ): RedirectResponse {
        $validated = $request->validate([
            'business_id' => ['required', 'string', 'exists:businesses,id'],
            'role_id' => ['required', 'string', 'exists:roles,id'],
            'overrides' => ['required', 'array'],
            'overrides.*' => ['required', 'string', 'in:inherit,allow,deny'],
        ]);

        $business = Business::findOrFail($validated['business_id']);
        $role = Role::findOrFail($validated['role_id']);

        try {
            $action->execute(
                actor: $request->user(),
                business: $business,
                role: $role,
                overrides: $validated['overrides']
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.tenant-controls.index', [
            'business_id' => $business->id,
            'role_id' => $role->id,
        ])->with('success', "Role overrides for '{$role->name}' in '{$business->name}' persisted successfully.");
    }
}
