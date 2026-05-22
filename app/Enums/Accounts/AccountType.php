<?php

namespace App\Enums\Accounts;

enum AccountType: string
{
    case CASH   = 'cash';
    case BANK   = 'bank';
    case MFS    = 'mfs';
    case WALLET = 'wallet';
    case LEDGER = 'ledger';

    public function label(): string
    {
        return match ($this) {
            self::CASH   => 'Cash',
            self::BANK   => 'Bank',
            self::MFS    => 'MFS',
            self::WALLET => 'Wallet',
            self::LEDGER => 'Ledger',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CASH   => 'bg-green-500',
            self::BANK   => 'bg-blue-500',
            self::MFS    => 'bg-pink-500',
            self::WALLET => 'bg-yellow-500',
            self::LEDGER => 'bg-gray-500',
        };
    }
}
