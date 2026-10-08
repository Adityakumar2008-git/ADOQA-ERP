<?php

namespace App\Actions\Rbac;

use App\Enums\RoleScope;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AssignRoleToMember
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Assign a business role to a member within the current tenant context.
     * Prevents cross-tenant bleed, self-escalation, and privilege escalation.
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, User $targetUser, Role $role): UserRole
    {
        // 1. Authorization: Actor must have staff.manage in current business
        if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
            throw new AuthorizationException('Unauthorized to assign roles in this business.');
        }

        // 2. Self-escalation protection: User cannot modify or assign roles to themselves
        if ($actor->id === $targetUser->id) {
            throw new DomainException('Self-escalation blocked: Users cannot assign or modify their own roles.');
        }

        // 3. Target member validation: Must be an active member of this specific business
        $targetMembership = $targetUser->membership;
        if (! $targetMembership || $targetMembership->business_id !== $business->id) {
            throw new DomainException("Target user does not belong to business [{$business->id}].");
        }

        if (! $targetMembership->isActive()) {
            throw new DomainException('Cannot assign roles to a member with suspended or inactive membership.');
        }

        if (! $targetUser->isActive()) {
            throw new DomainException('Cannot assign roles to an inactive or suspended user account.');
        }

        // 4. Role scope & tenant validation
        if ($role->scope !== RoleScope::Business) {
            throw new DomainException("Cannot assign non-business role [{$role->name}] within a business context.");
        }

        if ($role->business_id !== null && $role->business_id !== $business->id) {
            throw new DomainException("Cannot assign role owned by business [{$role->business_id}] to a member of business [{$business->id}].");
        }

        // 5. Privilege escalation protection: Actor cannot grant permissions they do not possess
        $role->loadMissing('permissions');
        foreach ($role->permissions as $perm) {
            if ($this->authorizationService->cannot($actor, $perm->slug, $business)) {
                throw new DomainException("Privilege escalation blocked: Cannot assign role [{$role->name}] containing permission [{$perm->slug}] which actor does not possess.");
            }
        }

        return DB::transaction(function () use ($actor, $business, $targetUser, $role) {
            $userRole = UserRole::firstOrCreate([
                'user_id' => $targetUser->id,
                'role_id' => $role->id,
                'business_id' => $business->id,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'role.assigned',
                'auditable_type' => UserRole::class,
                'auditable_id' => $userRole->id,
                'payload' => [
                    'target_user_id' => $targetUser->id,
                    'role_id' => $role->id,
                    'role_slug' => $role->slug,
                ],
            ]);

            $this->authorizationService->clearMemoized();

            return $userRole;
        });
    }
}
