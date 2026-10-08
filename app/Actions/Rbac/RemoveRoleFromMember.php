<?php

namespace App\Actions\Rbac;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RemoveRoleFromMember
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Remove a role assignment from a member.
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, User $targetUser, Role $role): void
    {
        // 1. Authorization: Actor must have staff.manage in current business
        if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
            throw new AuthorizationException('Unauthorized to remove roles in this business.');
        }

        // 2. Self-modification protection
        if ($actor->id === $targetUser->id) {
            throw new DomainException('Self-modification blocked: Users cannot modify their own roles.');
        }

        // 3. Target member validation
        $targetMembership = $targetUser->membership;
        if (! $targetMembership || $targetMembership->business_id !== $business->id) {
            throw new DomainException("Target user does not belong to business [{$business->id}].");
        }

        // 4. Primary owner protection
        if ($targetMembership->is_owner && $role->slug === 'owner') {
            throw new DomainException('Cannot remove the primary owner role from the business owner.');
        }

        $userRole = UserRole::where('user_id', $targetUser->id)
            ->where('role_id', $role->id)
            ->where('business_id', $business->id)
            ->first();

        if (! $userRole) {
            throw new DomainException("Target user is not assigned role [{$role->name}].");
        }

        DB::transaction(function () use ($actor, $business, $targetUser, $role, $userRole) {
            $userRoleId = $userRole->id;
            $userRole->delete();

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'role.removed',
                'auditable_type' => UserRole::class,
                'auditable_id' => $userRoleId,
                'payload' => [
                    'target_user_id' => $targetUser->id,
                    'role_id' => $role->id,
                    'role_slug' => $role->slug,
                ],
            ]);

            $this->authorizationService->clearMemoized();
        });
    }
}
