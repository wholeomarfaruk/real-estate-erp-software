<?php

namespace App\Enums\Property;

enum PropertySaleType:string
{
    case SALE = 'sale';
    case RENT = 'rent';

    public function label(): string
    {
        return match($this) {
            self::SALE => 'Sale',
            self::RENT => 'Rent',
        };
    }
}
