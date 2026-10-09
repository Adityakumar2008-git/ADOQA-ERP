<?php

namespace App\Actions\Tenancy;

use App\Enums\PermissionOverrideState;
use App\Enums\RoleScope;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessRolePermissionOverride;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SetBusinessRolePermissionOverride
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Configure a tenant-specific permission override for a business-scoped role.
     * Prevents modifying platform roles/permissions, cross-tenant leaks, privilege escalation,
     * and invalid states.
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(
        User $actor,
        Business $business,
        Role $role,
        Permission $permission,
        PermissionOverrideState|string $state
    ): ?BusinessRolePermissionOverride {
        $overrideState = is_string($state) ? PermissionOverrideState::from($state) : $state;

        // 1. Scope validations: Strictly reject platform-scoped roles and permissions
        if ($role->scope !== RoleScope::Business) {
            throw new DomainException("Cannot configure tenant overrides for platform-scoped role [{$role->slug}].");
        }

        if ($permission->scope !== RoleScope::Business) {
            throw new DomainException("Cannot configure tenant overrides for platform-scoped permission [{$permission->slug}].");
        }

        // 2. Tenant boundary: If custom role, must belong to this business
        if ($role->business_id !== null && $role->business_id !== $business->id) {
            throw new DomainException("Cannot modify role belonging to business [{$role->business_id}] from business [{$business->id}].");
        }

        // 3. Actor authorization
        if ($actor->isPlatformAdmin()) {
            if ($this->authorizationService->cannot($actor, 'platform.roles.manage') && $this->authorizationService->cannot($actor, 'businesses.update')) {
                throw new AuthorizationException('Unauthorized to configure tenant role overrides as platform administrator.');
            }
        } else {
            if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
                throw new AuthorizationException('Unauthorized to configure role overrides in this business.');
            }

            if ($actor->membership?->business_id !== $business->id) {
                throw new DomainException("Cannot configure overrides for business [{$business->id}] from another tenant context.");
            }

            // Privilege escalation prevention: Tenant actor cannot explicitly ALLOW a permission they do not possess
            if ($overrideState === PermissionOverrideState::Allow && $this->authorizationService->cannot($actor, $permission->slug, $business)) {
                throw new DomainException("Privilege escalation blocked: Cannot grant permission [{$permission->slug}] which actor does not possess.");
            }
        }

        return DB::transaction(function () use ($actor, $business, $role, $permission, $overrideState) {
            $existing = BusinessRolePermissionOverride::query()
                ->where('business_id', $business->id)
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->first();

            $oldState = $existing ? $existing->state->value : PermissionOverrideState::Inherit->value;

            if ($overrideState === PermissionOverrideState::Inherit) {
                if ($existing) {
                    $existing->delete();
                }
                $result = null;
            } else {
                $result = BusinessRolePermissionOverride::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'state' => $overrideState,
                        'updated_by' => $actor->id,
                    ]
                );
            }

            if ($oldState !== $overrideState->value) {
                AuditLog::create([
                    'business_id' => $business->id,
                    'user_id' => $actor->id,
                    'action' => 'role.permission_override_set',
                    'auditable_type' => Role::class,
                    'auditable_id' => $role->id,
                    'payload' => [
                        'role_slug' => $role->slug,
                        'permission_slug' => $permission->slug,
                        'old_state' => $oldState,
                        'new_state' => $overrideState->value,
                    ],
                ]);
            }

            // Invalidate authorization cache immediately
            $this->authorizationService->clearMemoized();

            return $result;
        });
    }
}
