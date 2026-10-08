<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleScope;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Display a listing of platform-scoped roles and permissions.
     * Restricted strictly to platform administrators with platform.roles.manage permission.
     */
    public function index(): JsonResponse
    {
        $platformRoles = Role::query()
            ->where('scope', RoleScope::Platform)
            ->with('permissions')
            ->get();

        $platformPermissions = Permission::query()
            ->where('scope', RoleScope::Platform)
            ->get();

        return response()->json([
            'roles' => $platformRoles,
            'permissions' => $platformPermissions,
        ]);
    }
}
