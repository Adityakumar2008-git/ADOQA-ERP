<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenancy\SetBusinessRolePermissionOverride;
use App\Enums\PermissionOverrideState;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessRolePermissionOverride;
use App\Models\Permission;
use App\Models\Role;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleOverrideController extends Controller
{
    /**
     * List all overrides for a specific role in current tenant.
     */
    public function index(Request $request, Role $role): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        if ($role->business_id !== null && $role->business_id !== $tenant->id) {
            abort(403, 'Unauthorized access to role belonging to another business.');
        }

        $overrides = BusinessRolePermissionOverride::query()
            ->where('business_id', $tenant->id)
            ->where('role_id', $role->id)
            ->with('permission')
            ->get();

        return response()->json([
            'role_id' => $role->id,
            'role_slug' => $role->slug,
            'overrides' => $overrides,
        ]);
    }

    /**
     * Set or clear a permission override for a role in current tenant.
     */
    public function store(
        Request $request,
        Role $role,
        SetBusinessRolePermissionOverride $action
    ): JsonResponse {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'permission_id' => ['required', 'string', 'exists:permissions,id'],
            'state' => ['required', 'string', Rule::enum(PermissionOverrideState::class)],
        ]);

        $permission = Permission::findOrFail($validated['permission_id']);
        $state = PermissionOverrideState::from($validated['state']);

        try {
            $override = $action->execute(
                actor: $request->user(),
                business: $tenant,
                role: $role,
                permission: $permission,
                state: $state
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Role permission override updated successfully.',
            'override' => $override,
        ]);
    }
}
