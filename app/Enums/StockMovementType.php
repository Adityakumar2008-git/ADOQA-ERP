<?php

namespace App\Enums;

enum StockMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case PurchaseReceipt = 'purchase_receipt';
    case StockAdjustment = 'stock_adjustment';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Damage = 'damage';
    case Loss = 'loss';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Opening Balance',
            self::PurchaseReceipt => 'Purchase Receipt',
            self::StockAdjustment => 'Stock Adjustment',
            self::TransferOut => 'Transfer Out',
            self::TransferIn => 'Transfer In',
            self::Damage => 'Damage',
            self::Loss => 'Loss',
            self::Reversal => 'Reversal',
        };
    }

    public function isAddition(): bool
    {
        return in_array($this, [
            self::OpeningBalance,
            self::PurchaseReceipt,
            self::TransferIn,
        ], true);
    }
}
