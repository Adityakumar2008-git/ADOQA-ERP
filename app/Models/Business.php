<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'legal_name',
    'slug',
    'business_type',
    'email',
    'phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country',
    'currency',
    'timezone',
    'tax_number',
    'status',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
        ];
    }

    /**
     * Members belonging to this business.
     */
    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }

    /**
     * Users associated with this business through memberships.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_members')
            ->withPivot(['is_owner', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * The primary owner membership of this business.
     */
    public function ownerMembership(): HasOne
    {
        return $this->hasOne(BusinessMember::class)->where('is_owner', true);
    }

    /**
     * Retrieve the primary owner user of this business.
     */
    public function getOwnerAttribute(): ?User
    {
        return $this->ownerMembership?->user;
    }

    /**
     * Check if the business is active and permitted to operate.
     */
    public function isActive(): bool
    {
        return $this->status === BusinessStatus::Active;
    }

    /**
     * Custom roles defined specifically by this business.
     */
    public function customRoles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * User role assignments in this business context.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Check if the business is currently suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === BusinessStatus::Suspended;
    }
}
