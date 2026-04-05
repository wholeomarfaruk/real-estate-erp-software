<?php

namespace App\Enums\Supplier;

enum SupplierPaymentMethod: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case MOBILE_BANKING = 'mobile_banking';
    case CHEQUE = 'cheque';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::MOBILE_BANKING => 'Mobile Banking',
            self::CHEQUE => 'Cheque',
            self::ADJUSTMENT => 'Adjustment',
        };
    }
}
