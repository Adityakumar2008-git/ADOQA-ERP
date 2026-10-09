<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case Draft = 'draft';
    case Prepared = 'prepared';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Prepared => 'Prepared',
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isPosted(): bool
    {
        return $this === self::Posted;
    }
}
