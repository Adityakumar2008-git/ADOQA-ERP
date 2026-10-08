<?php

namespace App\Enums;

enum RoleScope: string
{
    case Platform = 'platform';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Platform => 'Platform',
            self::Business => 'Business',
        };
    }
}
