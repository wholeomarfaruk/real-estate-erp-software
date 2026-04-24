<?php

namespace App\Enums\Accounts;

enum CollectionType: string
{
    case PROPERTY_SALE = 'property_sale';
    case RENT = 'rent';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PROPERTY_SALE => 'Property Sale',
            self::RENT => 'Rent',
            self::OTHER => 'Other',
        };
    }
}
