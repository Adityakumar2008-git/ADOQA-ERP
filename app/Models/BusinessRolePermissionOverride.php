<?php

namespace App\Models;

use App\Enums\PermissionOverrideState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'role_id',
    'permission_id',
    'state',
    'updated_by',
])]
class BusinessRolePermissionOverride extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'state' => PermissionOverrideState::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isAllow(): bool
    {
        return $this->state === PermissionOverrideState::Allow;
    }

    public function isDeny(): bool
    {
        return $this->state === PermissionOverrideState::Deny;
    }

    public function isInherit(): bool
    {
        return $this->state === PermissionOverrideState::Inherit;
    }
}
