<?php

namespace App\Models;

use App\Enums\RoleScope;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['business_id', 'name', 'slug', 'scope', 'description', 'is_system'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => RoleScope::class,
            'is_system' => 'boolean',
        ];
    }

    /**
     * The business this custom role belongs to (null for canonical/global roles).
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )->using(RolePermission::class)->withTimestamps();
    }

    /**
     * The user role assignments for this role.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Users assigned this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )->withPivot(['id', 'business_id'])->withTimestamps();
    }

    /**
     * Scope query to platform-scoped roles.
     */
    public function scopePlatform(Builder $query): Builder
    {
        return $query->where('scope', RoleScope::Platform);
    }

    /**
     * Scope query to business-scoped roles, optionally filtering by business.
     */
    public function scopeForBusiness(Builder $query, ?string $businessId = null): Builder
    {
        return $query->where('scope', RoleScope::Business)
            ->where(function (Builder $q) use ($businessId) {
                $q->whereNull('business_id');
                if ($businessId !== null) {
                    $q->orWhere('business_id', $businessId);
                }
            });
    }

    /**
     * Determine if this is a platform-scoped role.
     */
    public function isPlatformScope(): bool
    {
        return $this->scope === RoleScope::Platform;
    }

    /**
     * Determine if this is a business-scoped role.
     */
    public function isBusinessScope(): bool
    {
        return $this->scope === RoleScope::Business;
    }
}
