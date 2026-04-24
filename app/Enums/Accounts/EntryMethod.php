<?php

namespace App\Enums\Accounts;

enum EntryMethod: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case CHEQUE = 'cheque';
    case MOBILE_BANKING = 'mobile_banking';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::CHEQUE => 'Cheque',
            self::MOBILE_BANKING => 'Mobile Banking',
        };
    }
}
