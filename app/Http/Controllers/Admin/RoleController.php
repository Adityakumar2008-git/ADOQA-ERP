<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleScope;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of platform-scoped roles and permissions.
     * Restricted strictly to platform administrators with platform.roles.manage permission.
     */
    public function index(Request $request): View|JsonResponse
    {
        $platformRoles = Role::query()
            ->where('scope', RoleScope::Platform)
            ->with('permissions')
            ->get();

        $platformPermissions = Permission::query()
            ->where('scope', RoleScope::Platform)
            ->get();

        $systemBusinessRoles = Role::query()
            ->where('scope', RoleScope::Business)
            ->whereNull('business_id')
            ->with('permissions')
            ->get();

        $businessPermissions = Permission::query()
            ->where('scope', RoleScope::Business)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'roles' => $platformRoles,
                'permissions' => $platformPermissions,
                'business_roles' => $systemBusinessRoles,
                'business_permissions' => $businessPermissions,
            ]);
        }

        return view('admin.roles.index', [
            'platformRoles' => $platformRoles,
            'platformPermissions' => $platformPermissions,
            'systemBusinessRoles' => $systemBusinessRoles,
            'businessPermissions' => $businessPermissions,
        ]);
    }

    /**
     * Dedicated JSON endpoint for platform roles and permissions inspection.
     */
    public function api(): JsonResponse
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
