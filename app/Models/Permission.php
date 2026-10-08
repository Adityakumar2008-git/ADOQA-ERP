<?php

namespace App\Models;

use App\Enums\RoleScope;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'group', 'scope', 'description'])]
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
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
        ];
    }

    /**
     * Roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        )->using(RolePermission::class)->withTimestamps();
    }

    /**
     * Scope query to platform permissions.
     */
    public function scopePlatform(Builder $query): Builder
    {
        return $query->where('scope', RoleScope::Platform);
    }

    /**
     * Scope query to business permissions.
     */
    public function scopeBusiness(Builder $query): Builder
    {
        return $query->where('scope', RoleScope::Business);
    }

    /**
     * Scope query to permissions belonging to a specific group.
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}
