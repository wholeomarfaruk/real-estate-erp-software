<?php

namespace App\Enums\Accounts;

enum BankAccountType: string
{
    case BANK   = 'bank';
    case CASH   = 'cash';
    case MFS    = 'mfs';
    case WALLET = 'wallet';

    public function label(): string
    {
        return match($this) {
            self::BANK   => 'Bank',
            self::CASH   => 'Cash',
            self::MFS    => 'MFS',
            self::WALLET => 'Wallet',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::BANK   => '#0d2a4a',
            self::CASH   => '#b45309',
            self::MFS    => '#be185d',
            self::WALLET => '#6d28d9',
        };
    }

    public function tailwindBadgeClass(): string
    {
        return match($this) {
            self::BANK   => 'bg-blue-50 border-blue-200 text-blue-900',
            self::CASH   => 'bg-amber-50 border-amber-300 text-amber-800',
            self::MFS    => 'bg-pink-50 border-pink-200 text-pink-800',
            self::WALLET => 'bg-violet-50 border-violet-200 text-violet-800',
        };
    }
}
