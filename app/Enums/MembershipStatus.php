<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    /**
     * Determine if the membership is in good standing.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }

    /**
     * Human-readable label for the membership status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Inactive => 'Inactive',
        };
    }
}
