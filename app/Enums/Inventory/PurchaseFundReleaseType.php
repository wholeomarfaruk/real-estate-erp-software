<?php

namespace App\Enums\Inventory;

enum PurchaseFundReleaseType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case CREDIT = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank',
            self::CREDIT => 'Credit',
        };
    }
}
