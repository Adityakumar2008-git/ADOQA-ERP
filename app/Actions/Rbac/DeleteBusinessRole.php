<?php

namespace App\Actions\Rbac;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeleteBusinessRole
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Delete an existing custom business role.
     * System roles and foreign tenant roles can never be deleted.
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, Role $role): void
    {
        // 1. Authorization: Actor must have staff.manage in current business
        if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
            throw new AuthorizationException('Unauthorized to delete roles in this business.');
        }

        // 2. Canonical system role protection
        if ($role->is_system) {
            throw new DomainException("Canonical system role [{$role->slug}] cannot be deleted.");
        }

        // 3. Tenant boundary protection
        if ($role->business_id !== $business->id) {
            throw new DomainException("Cannot delete role belonging to business [{$role->business_id}] from business [{$business->id}].");
        }

        // 4. Active assignment protection
        if ($role->userRoles()->exists()) {
            throw new DomainException("Cannot delete role [{$role->name}] while members are actively assigned to it. Unassign members first.");
        }

        DB::transaction(function () use ($actor, $business, $role) {
            $roleId = $role->id;
            $roleName = $role->name;
            $roleSlug = $role->slug;

            $role->delete();

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'role.deleted',
                'auditable_type' => Role::class,
                'auditable_id' => $roleId,
                'payload' => [
                    'name' => $roleName,
                    'slug' => $roleSlug,
                ],
            ]);

            $this->authorizationService->clearMemoized();
        });
    }
}
