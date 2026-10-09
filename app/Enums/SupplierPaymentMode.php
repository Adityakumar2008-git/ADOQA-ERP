<?php

namespace App\Enums;

enum SupplierPaymentMode: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Upi = 'upi';
    case Card = 'card';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank Transfer (NEFT/RTGS/IMPS)',
            self::Upi => 'UPI',
            self::Card => 'Corporate Card',
            self::Cheque => 'Cheque',
        };
    }
}
