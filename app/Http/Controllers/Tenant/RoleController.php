<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Rbac\CreateBusinessRole;
use App\Actions\Rbac\DeleteBusinessRole;
use App\Actions\Rbac\SyncRolePermissions;
use App\Actions\Rbac\UpdateBusinessRole;
use App\Enums\RoleScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateRoleRequest;
use App\Http\Requests\Tenant\SyncRolePermissionsRequest;
use App\Http\Requests\Tenant\UpdateRoleRequest;
use App\Models\Business;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles available within the current tenant context.
     * Shows canonical business roles and custom roles created by this business.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $roles = Role::query()
            ->where('scope', RoleScope::Business)
            ->where(function ($query) use ($tenant) {
                $query->whereNull('business_id')
                    ->orWhere('business_id', $tenant->id);
            })
            ->with('permissions')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Store a new custom role within the current business.
     */
    public function store(CreateRoleRequest $request, CreateBusinessRole $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $role = $action->execute(
            actor: $request->user(),
            business: $tenant,
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Custom role created successfully.',
            'role' => $role,
        ], 201);
    }

    /**
     * Display the specified role and its permissions.
     */
    public function show(Request $request, Role $role): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        // Isolation check: If custom role belongs to another business, reject with 403
        if ($role->business_id !== null && $role->business_id !== $tenant->id) {
            abort(403, 'Unauthorized access to role belonging to another business.');
        }

        // Scope check: Platform roles cannot be inspected via business surface
        if ($role->scope !== RoleScope::Business) {
            abort(403, 'Platform roles cannot be accessed in a business context.');
        }

        return response()->json([
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Update the specified custom business role.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateBusinessRole $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $updatedRole = $action->execute(
            actor: $request->user(),
            business: $tenant,
            role: $role,
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $updatedRole,
        ]);
    }

    /**
     * Sync permissions assigned to this role.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissions $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $updatedRole = $action->execute(
            actor: $request->user(),
            business: $tenant,
            role: $role,
            permissionIdentifiers: $request->validated('permissions')
        );

        return response()->json([
            'message' => 'Role permissions updated successfully.',
            'role' => $updatedRole,
        ]);
    }

    /**
     * Remove the specified custom role from the business.
     */
    public function destroy(Request $request, Role $role, DeleteBusinessRole $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $action->execute(
            actor: $request->user(),
            business: $tenant,
            role: $role
        );

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
