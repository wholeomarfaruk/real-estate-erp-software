<?php

namespace App\Enums\Accounts;

enum TreasuryGroup: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case DIGITAL = 'digital';
    case NON_CASH = 'non_cash';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::DIGITAL => 'Digital/MFS',
            self::NON_CASH => 'Non-Cash',
        };
    }

    public function isLiquid(): bool
    {
        return in_array($this, [self::CASH, self::BANK, self::DIGITAL]);
    }

    public function isCash(): bool
    {
        return $this === self::CASH;
    }

    public function isDigital(): bool
    {
        return in_array($this, [self::DIGITAL]);
    }
}
