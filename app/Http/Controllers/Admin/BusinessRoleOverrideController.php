<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenancy\SetBusinessRolePermissionOverride;
use App\Enums\PermissionOverrideState;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessRoleOverrideController extends Controller
{
    /**
     * Store or update a tenant role-permission override.
     * Accessible exclusively to platform administrators with platform.roles.manage or businesses.update permission.
     */
    public function store(
        Request $request,
        Business $business,
        SetBusinessRolePermissionOverride $action
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'role_id' => ['required', 'string', 'exists:roles,id'],
            'permission_id' => ['required', 'string', 'exists:permissions,id'],
            'state' => ['required', 'string', Rule::enum(PermissionOverrideState::class)],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permission = Permission::findOrFail($validated['permission_id']);
        $state = PermissionOverrideState::from($validated['state']);

        try {
            $override = $action->execute(
                actor: $request->user(),
                business: $business,
                role: $role,
                permission: $permission,
                state: $state
            );
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Role permission override updated successfully.',
                'override' => $override,
            ]);
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', "Role override updated: [{$role->name}] -> [{$permission->name}] set to [{$state->label()}].");
    }
}
