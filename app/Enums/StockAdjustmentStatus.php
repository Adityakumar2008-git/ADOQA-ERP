<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isPosted(): bool
    {
        return $this === self::Posted;
    }
}
