<?php

namespace App\Actions\Rbac;

use App\Enums\RoleScope;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SyncRolePermissions
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Sync permissions assigned to a business role.
     * Prevents cross-scope attachment, system-role tampering, and privilege escalation.
     *
     * @param  array<string>  $permissionIdentifiers  (Array of ULIDs or slugs)
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, Role $role, array $permissionIdentifiers): Role
    {
        // 1. Authorization: Actor must have staff.manage in current business
        if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
            throw new AuthorizationException('Unauthorized to modify role permissions in this business.');
        }

        // 2. Tenant boundary protection
        if ($role->business_id !== null && $role->business_id !== $business->id) {
            throw new DomainException("Cannot modify role belonging to business [{$role->business_id}] from business [{$business->id}].");
        }

        // 3. System role permission modification boundaries:
        // Only custom roles and configurable system roles (manager) can have permissions modified.
        // Canonical owner, staff, cashier, platform_admin permissions are immutable.
        if ($role->is_system && $role->slug !== 'manager') {
            throw new DomainException("Canonical system role [{$role->slug}] permissions are fixed and cannot be modified.");
        }

        // 4. Resolve permissions
        $permissions = Permission::whereIn('id', $permissionIdentifiers)
            ->orWhereIn('slug', $permissionIdentifiers)
            ->get();

        foreach ($permissions as $perm) {
            // Scope validation: Platform permission cannot be attached to business role
            if ($role->scope === RoleScope::Business && $perm->scope !== RoleScope::Business) {
                throw new DomainException("Cannot attach platform permission [{$perm->slug}] to business role [{$role->name}].");
            }

            // Privilege escalation check: Actor cannot grant a permission they do not possess
            if ($this->authorizationService->cannot($actor, $perm->slug, $business)) {
                throw new DomainException("Privilege escalation blocked: Cannot grant permission [{$perm->slug}] which actor does not possess.");
            }
        }

        return DB::transaction(function () use ($actor, $business, $role, $permissions) {
            $oldPerms = $role->permissions()->pluck('slug')->toArray();

            $role->permissions()->sync($permissions->pluck('id'));

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'role.permissions_synced',
                'auditable_type' => Role::class,
                'auditable_id' => $role->id,
                'payload' => [
                    'role_slug' => $role->slug,
                    'old_permissions' => $oldPerms,
                    'new_permissions' => $permissions->pluck('slug')->toArray(),
                ],
            ]);

            $this->authorizationService->clearMemoized();

            return $role->load('permissions');
        });
    }
}
