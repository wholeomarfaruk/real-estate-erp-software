<?php

namespace App\Enums\Accounts;

enum AccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case MFS = 'mfs';
    case WALLET = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::MFS => 'MFS',
            self::WALLET => 'Wallet',
        };
    }
}
