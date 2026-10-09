<?php

namespace App\Enums;

enum SupplierLedgerEntryType: string
{
    case BillPosted = 'bill_posted';
    case PaymentPosted = 'payment_posted';
    case PaymentReversed = 'payment_reversed';
    case AdvanceRecorded = 'advance_recorded';
    case AdvanceAllocated = 'advance_allocated';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::BillPosted => 'Purchase Bill Liability',
            self::PaymentPosted => 'Supplier Payment',
            self::PaymentReversed => 'Payment Reversal',
            self::AdvanceRecorded => 'Supplier Advance Recorded',
            self::AdvanceAllocated => 'Advance Allocated to Bill',
            self::Adjustment => 'Ledger Adjustment',
        };
    }

    /**
     * Positive amount increases supplier payable obligation; negative decreases it.
     */
    public function increasesPayable(): bool
    {
        return in_array($this, [self::BillPosted, self::PaymentReversed], true);
    }
}
