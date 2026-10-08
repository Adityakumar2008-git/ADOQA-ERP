<?php

namespace App\Services\Auth;

use App\Enums\RoleScope;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;

class AuthorizationService
{
    /**
     * In-memory memoized permission slugs per user and context key.
     * Format: [ "user_{id}_platform" => ['perm.slug' => true], "user_{id}_biz_{business_id}" => [...] ]
     *
     * @var array<string, array<string, bool>>
     */
    protected array $memoizedPermissions = [];

    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Core authorization check: Determine whether the user possesses the specified permission.
     * Strictly deny-by-default.
     */
    public function can(?User $user, string $permissionSlug, ?Business $businessContext = null): bool
    {
        if (! $user || ! $user->isActive()) {
            return false;
        }

        $permission = Permission::query()->where('slug', $permissionSlug)->first();

        if (! $permission) {
            return false; // Permission does not exist in the system -> deny
        }

        if ($permission->scope === RoleScope::Platform) {
            return $this->evaluatePlatformPermission($user, $permission);
        }

        if ($permission->scope === RoleScope::Business) {
            $context = $businessContext ?? $this->tenantContext->current() ?? $user->currentBusiness();

            return $this->evaluateBusinessPermission($user, $permission, $context);
        }

        return false;
    }

    /**
     * Inverse of can().
     */
    public function cannot(?User $user, string $permissionSlug, ?Business $businessContext = null): bool
    {
        return ! $this->can($user, $permissionSlug, $businessContext);
    }

    /**
     * Authorize or throw AuthorizationException.
     *
     * @throws AuthorizationException
     */
    public function authorize(User $user, string $permissionSlug, ?Business $businessContext = null): void
    {
        if ($this->cannot($user, $permissionSlug, $businessContext)) {
            throw new AuthorizationException("Unauthorized action for permission [{$permissionSlug}].");
        }
    }

    /**
     * Determine if a user can access a specific business.
     */
    public function canAccessBusiness(User $user, Business|string $business): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $businessId = $business instanceof Business ? $business->id : $business;
        $membership = $user->membership;

        if (! $membership || ! $membership->isActive() || $membership->business_id !== $businessId) {
            return false;
        }

        $targetBusiness = $business instanceof Business ? $business : Business::find($businessId);

        return $targetBusiness !== null && $targetBusiness->isActive();
    }

    /**
     * Determine if a user has platform administration scope access.
     */
    public function canAccessPlatform(User $user): bool
    {
        if (! $user->isActive() || ! $user->isPlatformAdmin()) {
            return false;
        }

        // Verify user has an assigned platform role
        return $user->userRoles()
            ->whereNull('business_id')
            ->whereHas('role', function ($query) {
                $query->where('scope', RoleScope::Platform);
            })
            ->exists();
    }

    /**
     * Clear memoized authorization cache (useful for testing or after role mutation).
     */
    public function clearMemoized(): void
    {
        $this->memoizedPermissions = [];
    }

    /**
     * Evaluate platform permission.
     * Platform permissions can ONLY be granted by platform-scoped roles (business_id IS NULL).
     */
    protected function evaluatePlatformPermission(User $user, Permission $permission): bool
    {
        // User must have platform admin flag
        if (! $user->isPlatformAdmin()) {
            return false;
        }

        $cacheKey = "user_{$user->id}_platform";

        if (! isset($this->memoizedPermissions[$cacheKey])) {
            $this->loadPlatformPermissions($user, $cacheKey);
        }

        return isset($this->memoizedPermissions[$cacheKey][$permission->slug]);
    }

    /**
     * Evaluate business permission.
     * Requires active user, active membership, active business, and matching business role assignment.
     */
    protected function evaluateBusinessPermission(User $user, Permission $permission, ?Business $business): bool
    {
        if (! $business || ! $business->isActive()) {
            return false;
        }

        $membership = $user->membership;

        if (! $membership || ! $membership->isActive() || $membership->business_id !== $business->id) {
            return false;
        }

        $cacheKey = "user_{$user->id}_biz_{$business->id}";

        if (! isset($this->memoizedPermissions[$cacheKey])) {
            $this->loadBusinessPermissions($user, $business, $cacheKey);
        }

        return isset($this->memoizedPermissions[$cacheKey][$permission->slug]);
    }

    /**
     * Load all platform-granted permission slugs for the user.
     */
    protected function loadPlatformPermissions(User $user, string $cacheKey): void
    {
        $this->memoizedPermissions[$cacheKey] = [];

        $userRoles = $user->userRoles()
            ->whereNull('business_id')
            ->with(['role.permissions'])
            ->get();

        foreach ($userRoles as $userRole) {
            $role = $userRole->role;
            if ($role && $role->scope === RoleScope::Platform) {
                foreach ($role->permissions as $perm) {
                    if ($perm->scope === RoleScope::Platform) {
                        $this->memoizedPermissions[$cacheKey][$perm->slug] = true;
                    }
                }
            }
        }
    }

    /**
     * Load all business-granted permission slugs for the user within the specific business.
     */
    protected function loadBusinessPermissions(User $user, Business $business, string $cacheKey): void
    {
        $this->memoizedPermissions[$cacheKey] = [];

        $userRoles = $user->userRoles()
            ->where('business_id', $business->id)
            ->with(['role.permissions'])
            ->get();

        foreach ($userRoles as $userRole) {
            $role = $userRole->role;

            if (! $role || $role->scope !== RoleScope::Business) {
                continue;
            }

            // If it's a custom role, it must belong to this business
            if ($role->business_id !== null && $role->business_id !== $business->id) {
                continue;
            }

            foreach ($role->permissions as $perm) {
                if ($perm->scope === RoleScope::Business) {
                    $this->memoizedPermissions[$cacheKey][$perm->slug] = true;
                }
            }
        }
    }
}
