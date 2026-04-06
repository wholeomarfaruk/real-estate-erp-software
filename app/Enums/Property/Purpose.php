<?php

namespace App\Enums\Property;

enum Purpose: string
{
    case SELL = 'sell';
    case RENT = 'rent';
    case SELL_RENT = 'sell_rent';

    public function label(): string
    {
        return match ($this) {
            self::SELL => 'Sell',
            self::RENT => 'Rent',
            self::SELL_RENT => 'Sell / Rent',
        };
    }
}
