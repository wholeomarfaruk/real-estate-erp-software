<?php

namespace App\Enums\Inventory;

enum PurchaseMode: string
{
    case CASH = 'cash';
    case CREDIT = 'credit';
    case CHEQUE = 'cheque';
    case CASH_AND_CREDIT = 'cash_and_credit';
    case CASH_AND_CHEQUE = 'cash_and_cheque';
    case CREDIT_AND_CHEQUE = 'credit_and_cheque';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CREDIT => 'Credit',
            self::CHEQUE => 'Cheque',
            self::CASH_AND_CREDIT => 'Cash and Credit',
            self::CASH_AND_CHEQUE => 'Cash and Cheque',
            self::CREDIT_AND_CHEQUE => 'Credit and Cheque',
        };
    }
}
