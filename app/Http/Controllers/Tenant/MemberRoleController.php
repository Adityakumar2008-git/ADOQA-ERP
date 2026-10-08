<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Rbac\AssignRoleToMember;
use App\Actions\Rbac\RemoveRoleFromMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AssignMemberRoleRequest;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberRoleController extends Controller
{
    /**
     * Assign a role to a business member.
     */
    public function store(AssignMemberRoleRequest $request, User $user, AssignRoleToMember $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $role = Role::findOrFail($request->validated('role_id'));

        $userRole = $action->execute(
            actor: $request->user(),
            business: $tenant,
            targetUser: $user,
            role: $role
        );

        return response()->json([
            'message' => 'Role assigned to member successfully.',
            'user_role' => $userRole->load('role'),
        ], 201);
    }

    /**
     * Remove a role from a business member.
     */
    public function destroy(Request $request, User $user, Role $role, RemoveRoleFromMember $action): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $action->execute(
            actor: $request->user(),
            business: $tenant,
            targetUser: $user,
            role: $role
        );

        return response()->json([
            'message' => 'Role removed from member successfully.',
        ]);
    }
}
