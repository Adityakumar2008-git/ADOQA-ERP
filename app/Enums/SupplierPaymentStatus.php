<?php

namespace App\Enums;

enum SupplierPaymentStatus: string
{
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Posted => 'Posted',
            self::Reversed => 'Reversed',
        };
    }

    public function isPosted(): bool
    {
        return $this === self::Posted;
    }
}
