<?php

namespace App\Enums;

enum PermissionOverrideState: string
{
    case Inherit = 'inherit';
    case Allow = 'allow';
    case Deny = 'deny';

    public function label(): string
    {
        return match ($this) {
            self::Inherit => 'Inherit Default',
            self::Allow => 'Explicit Allow',
            self::Deny => 'Explicit Deny',
        };
    }
}
