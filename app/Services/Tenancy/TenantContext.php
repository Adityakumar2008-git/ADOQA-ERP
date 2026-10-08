<?php

namespace App\Services\Tenancy;

use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Exceptions\TenantAccessDeniedException;
use App\Models\Business;
use App\Models\User;

class TenantContext
{
    protected ?Business $currentTenant = null;

    /**
     * Retrieve the currently resolved business tenant.
     */
    public function current(): ?Business
    {
        return $this->currentTenant;
    }

    /**
     * Retrieve the current business ID or null.
     */
    public function id(): ?string
    {
        return $this->currentTenant?->id;
    }

    /**
     * Explicitly set the active tenant context for the current process/request.
     */
    public function set(Business $business): void
    {
        if (! $business->isActive()) {
            throw new TenantAccessDeniedException('This business is inactive or suspended.');
        }

        $this->currentTenant = $business;
    }

    /**
     * Clear the current tenant context.
     */
    public function clear(): void
    {
        $this->currentTenant = null;
    }

    /**
     * Check if a tenant context has been resolved.
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Resolve and validate tenant context strictly from the authenticated user.
     * Never trusts client-provided tenant identifiers.
     *
     * @throws TenantAccessDeniedException
     */
    public function resolveForUser(User $user): Business
    {
        // Platform admin without membership has no default business membership
        if ($user->isPlatformAdmin() && ! $user->membership) {
            throw new TenantAccessDeniedException('Platform administrator has no active business membership.');
        }

        $membership = $user->membership;

        if (! $membership || $membership->status !== MembershipStatus::Active) {
            throw new TenantAccessDeniedException('No active membership found for this business.');
        }

        $business = $membership->business;

        if (! $business || $business->status !== BusinessStatus::Active) {
            throw new TenantAccessDeniedException('The business is currently inactive or suspended.');
        }

        $this->set($business);

        return $business;
    }
}
