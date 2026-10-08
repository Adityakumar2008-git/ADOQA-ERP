<?php

namespace App\Models;

use App\Enums\RoleScope;
use Database\Factories\UserRoleFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'role_id', 'business_id'])]
class UserRole extends Model
{
    /** @use HasFactory<UserRoleFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::saving(function (UserRole $userRole) {
            $userRole->validateSecurityInvariants();
        });
    }

    /**
     * The user this role assignment belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The role assigned.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * The business context for this role assignment (null for platform roles).
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Validate cross-tenant and scope security invariants before persisting.
     *
     * @throws DomainException
     */
    public function validateSecurityInvariants(): void
    {
        $role = $this->role ?? Role::find($this->role_id);

        if (! $role) {
            return; // Let foreign key constraint handle non-existent role
        }

        // 1. Platform-scoped roles must not have a business_id
        if ($role->scope === RoleScope::Platform && $this->business_id !== null) {
            throw new DomainException('Platform role cannot be assigned with a business context.');
        }

        // 2. Business-scoped roles must have a business_id
        if ($role->scope === RoleScope::Business && $this->business_id === null) {
            throw new DomainException('Business role must be assigned with a non-null business context.');
        }

        // 3. Custom roles owned by Business A cannot be assigned in Business B's context
        if ($role->business_id !== null && $role->business_id !== $this->business_id) {
            throw new DomainException("Cannot assign role owned by business [{$role->business_id}] to business [{$this->business_id}].");
        }

        // 4. Verify user's business membership matches the assignment business context
        $user = $this->user ?? User::find($this->user_id);
        if ($user && $role->scope === RoleScope::Business) {
            $membership = $user->membership;
            if ($membership && $membership->business_id !== $this->business_id) {
                throw new DomainException("Cannot assign business [{$this->business_id}] role to user member of business [{$membership->business_id}].");
            }
        }
    }
}
