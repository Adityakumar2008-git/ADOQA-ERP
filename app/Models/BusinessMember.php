<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use Database\Factories\BusinessMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'user_id',
    'is_owner',
    'status',
    'joined_at',
])]
class BusinessMember extends Model
{
    /** @use HasFactory<BusinessMemberFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'status' => MembershipStatus::class,
            'joined_at' => 'datetime',
        ];
    }

    /**
     * The business to which this membership belongs.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The user associated with this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if this member is active.
     */
    public function isActive(): bool
    {
        return $this->status === MembershipStatus::Active;
    }

    /**
     * Determine if this member is designated as the business owner.
     */
    public function isOwner(): bool
    {
        return $this->is_owner === true;
    }
}
