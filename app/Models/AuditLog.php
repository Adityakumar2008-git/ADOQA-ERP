<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'user_id',
    'action',
    'auditable_type',
    'auditable_id',
    'payload',
    'ip_address',
    'user_agent',
])]
class AuditLog extends Model
{
    use HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * The business this audit log belongs to (null for platform audit events).
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The user / actor who triggered this audit event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
