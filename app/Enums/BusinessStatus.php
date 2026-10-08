<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    /**
     * Determine if the business status allows normal commercial operations.
     */
    public function canOperate(): bool
    {
        return $this === self::Active;
    }

    /**
     * Human-readable label for the business status.
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
