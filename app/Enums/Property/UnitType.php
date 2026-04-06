<?php

namespace App\Enums\Property;

enum UnitType: string
{
    case FLAT = 'flat';
    case SHOP = 'shop';
    case COMMUNITY_CENTER = 'community_center';
    case OFFICE = 'office';
    case PARKING = 'parking';
    case SHOWROOM = 'showroom';
    case WAREHOUSE = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::FLAT => 'Flat',
            self::SHOP => 'Shop',
            self::COMMUNITY_CENTER => 'Community Center',
            self::OFFICE => 'Office',
            self::PARKING => 'Parking',
            self::SHOWROOM => 'Showroom',
            self::WAREHOUSE => 'Warehouse',
        };
    }
}
