<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'status', 'is_platform_admin', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * The business membership for an ordinary user account.
     */
    public function membership(): HasOne
    {
        return $this->hasOne(BusinessMember::class);
    }

    /**
     * The business associated with this user through their membership.
     */
    public function business(): HasOneThrough
    {
        return $this->hasOneThrough(
            Business::class,
            BusinessMember::class,
            'user_id',
            'id',
            'id',
            'business_id'
        );
    }

    /**
     * Role assignments for this user.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        )->withPivot(['id', 'business_id'])->withTimestamps();
    }

    /**
     * Determine if the user is in active status.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Determine if the user is currently suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /**
     * Determine if the user has platform administrator privileges.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin === true;
    }

    /**
     * Check if this user belongs to the specified business.
     */
    public function belongsToBusiness(Business|string $business): bool
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        return $this->membership?->business_id === $businessId;
    }

    /**
     * Retrieve the current active business for this user.
     */
    public function currentBusiness(): ?Business
    {
        if (! $this->membership || $this->membership->status !== MembershipStatus::Active) {
            return null;
        }

        return $this->business;
    }
}
